<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\GroupedFormType;
use LotGD2\Form\Scene\SceneTemplate\InnTemplateType;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\GameStateService;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Handler\CharmHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Twig\Component\Admin\GameSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @phpstan-type InnTemplateConfiguration array{
 *      innName: string,
 *      innKeeper: array{
 *          name: string,
 *          banter: string,
 *          offerAlcohol: bool,
 *          alcoholPrice: string,
 *          drunkennessAmount: int,
 *          drunkennessLimit: int,
 *          texts: array{
 *             intro: string,
 *             buyAlcohol: string,
 *          },
 *      },
 *      minCharmPoints: int,
 *      maxCharmPoints: int,
 *      malePatron: array{
 *          name: string,
 *          comment: string,
 *          friend: array{
 *              intro: string,
 *              judge: string,
 *          },
 *          flirt: array{
 *              intro: string,
 *              hasSeenLoverMessage: string,
 *          },
 *          flirts: InnFlirtOption[],
 *      },
 *      femalePatron: array{
 *          name: string,
 *          comment: string,
 *          friend: array{
 *              intro: string,
 *              gossip: string,
 *              judge: string,
 *          },
 *          flirt: array{
 *              intro: string,
 *              hasSeenLoverMessage: string,
 *          },
 *          flirts: InnFlirtOption[],
 *      },
 *  }
 * @phpstan-type InnTemplateDefaultContext array{
 *      patron: array{
 *          male: string,
 *          female: string,
 *      },
 *      patronBanter: array{
 *           male: string,
 *           female: string,
 *      },
 *      innKeeper: string,
 *      innKeeperBanter: string,
 *  }
 * @implements SceneTemplateInterface<InnTemplateConfiguration>
 * @phpstan-import-type GameSettingsDataType from GameSettings
 * @phpstan-type DrunkennessGameSetting array{
 *     hangOverLimit: int,
 *     allowAlcohol: bool,
 * }
 */
#[Autoconfigure(public: true)]
#[TemplateType(InnTemplateType::class)]
class InnTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const array ActionGroup = [
        "chat" => "lotgd2_actionGroup_innTemplate_chat",
        "femalePatron" => "lotgd2_actionGroup_innTemplate_femalePatron",
        "malePatron" => "lotgd2_actionGroup_innTemplate_malePatron",
        "innKeeper" => "lotgd2_actionGroup_innTemplate_innKeeper",
    ];

    const array Action = [
        "femalePatron" => "lotgd2_action_innTemplate_patron_female",
        "femalePatronGossip" => "lotgd2_action_innTemplate_femalePatron_gossip",
        "femalePatronJudge" => "lotgd2_action_innTemplate_femalePatron_judge",
        "femalePatronFlirt" => "lotgd2_action_innTemplate_femalePatron_flirt",
        "malePatron" => "lotgd2_action_innTemplate_patron_male",
        "malePatronGossip" => "lotgd2_action_innTemplate_malePatron_gossip",
        "malePatronJudge" => "lotgd2_action_innTemplate_malePatron_judge",
        "malePatronFlirt" => "lotgd2_action_innTemplate_malePatron_flirt",
        "innKeeper" => "lotgd2_action_innTemplate_patron_innKeeper",
        "otherPatrons" => "lotgd2_action_innTemplate_patron_others",
    ];

    const array Paragraphs = [
        "otherBanter" =>"lotgd2_paragraph_inn_banter_others",
        "femaleBanter" =>"lotgd2_paragraph_inn_female_banter",
        "maleBanter" =>"lotgd2_paragraph_inn_male_banter",
        "innKeeper" => "lotgd2_paragraph_inn_newday_innKeeper",
        "drunkAlcohol" => "lotgd2_paragraph_inn_newday_drunkAlcohol",
        "exhausted" => "lotgd2_paragraph_inn_exhausted",
        "hangover" => "lotgd2_paragraph_inn_newday_hangover",
    ];

    const string SeenLoverProperty = "lotgd2_property_innTemplate_seenMaster";
    const string DrunkenessProperty = "lotgd2_property_innTemplate_drunkeness";
    const string GameSettingProperty = "lotgd2_innTemplate";
    const string DrunkennessBuffId = "lotgd2_buff_innTemplate_drunkenness";

    /**
     * @var DrunkennessGameSetting
     */
    private array $drunkennessGameSetting;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DiceBagInterface $diceBag,
        private readonly ExpressionService $expressionService,
        private readonly GameStateService $gameStateService,
        private readonly GenderHandler $genderHandler,
        private readonly CharmHandler $charmHandler,
        private readonly HealthHandler $healthHandler,
        private readonly GoldHandler $goldHandler,
        private readonly BuffHandler $buffHandler,
    ) {
        $this->drunkennessGameSetting = $this->gameStateService->getSetting(self::GameSettingProperty, []);
    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameter("op") ?? null;
        $this->logger->debug("Called InnTemplate::onSceneChange, op={$op}");

        match($op) {
            default => $this->defaultAction(),
            "otherPatrons" => $this->otherPatronsAction(),
            "femalePatron" => $this->femalePatronAction(),
            "malePatron" => $this->malePatronAction(),
            "innKeeper" => $this->innKeeperAction(),
        };
    }

    /**
     * @return void
     */
    public function defaultAction(): void
    {
        $defaultTextParagraph = $this->stage->paragraphs[Stage::SceneText];
        $defaultTextParagraph->context = $this->getDefaultContext();

        $this->addDefaultActions();
    }

    public function otherPatronsAction(): void
    {
        $this->stage->paragraphs = [
            new Paragraph(
                id: self::Paragraphs["otherBanter"],
                text: "You stroll over to a table, place your foot up on the bench and listen in on the conversation.",
                context: $this->getDefaultContext(),
            )
        ];

        $this->addDefaultActions();
    }

    public function innKeeperAction(): void
    {
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;
        $canOfferAlcohol = ($this->drunkennessGameSetting["allowAlcohol"]??true) && ($config["innKeeper"]["offerAlcohol"]??true);
        $alcoholPrice = $this->expressionService->evaluateInteger($this->character, $config["innKeeper"]["alcoholPrice"]??"character.level*10");
        $act = $this->action->getParameter("act");

        switch ($act) {
            case "buyAlcohol":
                $alcoholPrice = $this->action->getParameter("price") ?? $alcoholPrice;
                $drunkenness = $this->getDrunkenness($this->character);
                $maxDrunkenness = $config["innKeeper"]["drunkennessLimit"];
                $drunkennessIncrease = $config["innKeeper"]["drunkennessAmount"] ?? 33;

                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["innKeeper"],
                        text: $config["innKeeper"]["texts"]["buyAlcohol"] ?? "innKeeper.texts.buyAlcohol",
                        context: [
                            ... $this->getDefaultContext(),
                            "drunkenness" => $drunkenness,
                            "maxDrunkenness" => $maxDrunkenness,
                            "price" => $alcoholPrice,
                        ],
                    )
                ];

                if ($drunkenness <= $maxDrunkenness) {
                    // Only do something when drunkenness is below or equal to limit
                    // The configured text should do something about when above the drunkenness level.

                    if ($this->goldHandler->getGold($this->character) >= $alcoholPrice) {
                        // And only do something when the character has enough gold
                        $this->goldHandler->removeGold($this->character, $alcoholPrice);
                        $this->addDrunkenness($this->character, $drunkennessIncrease);

                        //
                        if ($this->diceBag->chance(75)) {
                            $this->logger->debug("{$this->character} drunk alcohol and feels healthy");
                            $this->stage->addParagraph(new Paragraph(
                                id: self::Paragraphs["drunkAlcohol"],
                                text: "You feel healthy!"
                            ));

                            $this->healthHandler->heal(
                                (int)round($this->healthHandler->getMaxHealth($this->character)*0.1, 0),
                                $this->character
                            );
                        } else {
                            $this->logger->debug("{$this->character} drunk alcohol and feels vigorous");
                            $this->stage->addParagraph(new Paragraph(
                                id: self::Paragraphs["drunkAlcohol"],
                               text:  "You feel vigorous!"
                            ));

                            $this->healthHandler->addTurns(1, $this->character);
                        }

                        // Buff
                        $this->buffHandler->addBuff($this->character, new Buff(
                            id: self::DrunkennessBuffId,
                            name: "Buzz",
                            activatesAt: Buff::ACTIVATES_ON_OFFENSE_TURN,
                            rounds: 10,
                            roundMessage: "You've got a nice buzz going.",
                            endMessage: "Your buzz fades.",
                            expiresOnNewDay: true,
                            goodGuyAttackModifier: 1.25,
                        ));
                    }
                }
                break;

            default:
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["innKeeper"],
                        text: $config["innKeeper"]["texts"]["intro"] ?? "innKeeper.texts.intro",
                        context: $this->getDefaultContext(),
                    )
                ];

                $actionGroup = new ActionGroup(
                    id: self::ActionGroup["innKeeper"],
                    title: $config["innKeeper"]["name"] ?? "Cedrik",
                    weight: 10,
                );

                $this->stage->addActionGroup($actionGroup);

                if ($canOfferAlcohol) {
                    $actionGroup->addAction(new Action(
                        scene: $this->scene,
                        title: "Ale (" . $alcoholPrice . " gold)",
                        parameters: [
                            "op" => "innKeeper",
                            "act" => "buyAlcohol",
                            "price" => $alcoholPrice,
                        ]
                    ));
                }

                $this->addDefaultActions();
                break;
        }
    }

    public function femalePatronAction(): void
    {
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;
        $act = $this->action->getParameter("act");

        switch ($act) {
            case "gossip":
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["femaleBanter"],
                        text: $config["femalePatron"]["friend"]["gossip"] ?? "",
                        context: $this->getDefaultContext(),
                    )
                ];
                break;

            case "judge":
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["femaleBanter"],
                        text: $config["femalePatron"]["friend"]["judge"] ?? "",
                        context: $this->getDefaultContext(),
                    )
                ];
                break;

            case "flirt":
                $flirtOptionId = $this->action->getParameter("flirt");
                $this->flirtAction("female", $flirtOptionId ?? 0);
                break;

            default:
                if ($this->genderHandler->getPreferredPartner($this->character) === 'female') {
                    // Flirt options
                    if ($this->getSeenLover($this->character)) {
                        $this->stage->paragraphs = [
                            new Paragraph(
                                id: self::Paragraphs["femaleBanter"],
                                text: $config["femalePatron"]["flirt"]["hasSeenLoverMessage"] ?? "/femalePatron.flirt.hasSeenLoverMessage/",
                                context: $this->getDefaultContext(),
                            )
                        ];
                    } else {
                        // Has not yet seen lover
                        $this->stage->paragraphs = [
                            new Paragraph(
                                id: self::Paragraphs["femaleBanter"],
                                text: $config["femalePatron"]["flirt"]["intro"] ?? "",
                                context: $this->getDefaultContext(),
                            )
                        ];

                        // Flirt options
                        $actionGroup = new ActionGroup(
                            id: self::ActionGroup["femalePatron"],
                            title: "Flirt with " . ($config["femalePatron"]["name"] ?? "Violet"),
                            weight: 10,
                        );

                        $actions = [];
                        foreach (($config["femalePatron"]["flirts"] ?? []) as $key => $flirt) {
                            $actions[] = new Action(
                                scene: $this->scene,
                                title: $flirt->name,
                                parameters: ["op" => "femalePatron", "act" => "flirt", "flirt" => $key],
                                reference: self::Action["femalePatronFlirt"] . "_" . $key,
                            );
                        }

                        $actionGroup->setActions($actions);

                        if (count($actions) > 0) {
                            $this->stage->addActionGroup($actionGroup);
                        }
                    }
                } else {
                    $this->stage->paragraphs = [
                        new Paragraph(
                            id: self::Paragraphs["femaleBanter"],
                            text: $config["femalePatron"]["friend"]["intro"] ?? "",
                            context: $this->getDefaultContext(),
                        )
                    ];

                    // Chat options
                    $actionGroup = new ActionGroup(
                        id: self::ActionGroup["femalePatron"],
                        title: "Chat with " . ($config["femalePatron"]["name"] ?? "Violet" ),
                        weight: 10,
                        actions: [
                            new Action(
                                scene: $this->scene,
                                title: "Gossip",
                                parameters: ["op" => "femalePatron", "act" => "gossip"],
                                reference: self::Action["femalePatronGossip"],
                            ),
                            new Action(
                                scene: $this->scene,
                                title: "Ask her what she thinks about you",
                                parameters: ["op" => "femalePatron", "act" => "judge"],
                                reference: self::Action["femalePatronJudge"],
                            ),
                        ],
                    );

                    $this->stage->addActionGroup($actionGroup);
                }

                break;
        }

        $this->addDefaultActions();
    }

    public function malePatronAction(): void
    {
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;
        $act = $this->action->getParameter("act");

        switch ($act) {
            case "gossip":
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["maleBanter"],
                        text: $config["malePatron"]["friend"]["gossip"] ?? "",
                        context: $this->getDefaultContext(),
                    )
                ];
                break;

            case "judge":
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: self::Paragraphs["maleBanter"],
                        text: $config["malePatron"]["friend"]["judge"] ?? "",
                        context: $this->getDefaultContext(),
                    )
                ];
                break;

            case "flirt":
                $flirtOptionId = $this->action->getParameter("flirt");
                $this->flirtAction("male", $flirtOptionId ?? 0);
                break;

            default:
                if ($this->genderHandler->getPreferredPartner($this->character) === 'male') {
                    // Flirt options
                    if ($this->getSeenLover($this->character)) {
                        $this->stage->paragraphs = [
                            new Paragraph(
                                id: self::Paragraphs["maleBanter"],
                                text: $config["malePatron"]["flirt"]["hasSeenLoverMessage"] ?? "/malePatron.flirt.hasSeenLoverMessage/",
                                context: $this->getDefaultContext(),
                            )
                        ];
                    } else {
                        $this->stage->paragraphs = [
                            new Paragraph(
                                id: self::Paragraphs["maleBanter"],
                                text: $config["malePatron"]["flirt"]["intro"] ?? "/malePatron.flirt.intro/",
                                context: $this->getDefaultContext(),
                            )
                        ];

                        // Flirt options
                        $actionGroup = new ActionGroup(
                            id: self::ActionGroup["malePatron"],
                            title: "Flirt with " . ($config["malePatron"]["name"] ?? "Seth"),
                            weight: 10,
                        );

                        $actions = [];
                        foreach (($config["malePatron"]["flirts"] ?? []) as $key => $flirt) {
                            $actions[] = new Action(
                                scene: $this->scene,
                                title: $flirt->name,
                                parameters: ["op" => "malePatron", "act" => "flirt", "flirt" => $key],
                                reference: self::Action["malePatronFlirt"] . "_" . $key,
                            );
                        }

                        $actionGroup->setActions($actions);

                        if (count($actions) > 0) {
                            $this->stage->addActionGroup($actionGroup);
                        }
                    }
                } else {
                    // Friend options
                    $this->stage->paragraphs = [
                        new Paragraph(
                            id: self::Paragraphs["maleBanter"],
                            text: $config["malePatron"]["friend"]["intro"] ?? "/malePatron.friend.intro/",
                            context: $this->getDefaultContext(),
                        )
                    ];

                    // Chat options
                    $actionGroup = new ActionGroup(
                        id: self::ActionGroup["malePatron"],
                        title: "Chat with " . ($config["malePatron"]["name"] ?? "Seth" ),
                        weight: 10,
                        actions: [
                            new Action(
                                scene: $this->scene,
                                title: "Gossip",
                                parameters: ["op" => "malePatron", "act" => "gossip"],
                                reference: self::Action["malePatronGossip"],
                            ),
                            new Action(
                                scene: $this->scene,
                                title: "Ask her what she thinks about you",
                                parameters: ["op" => "malePatron", "act" => "judge"],
                                reference: self::Action["malePatronJudge"],
                            ),
                        ],
                    );

                    $this->stage->addActionGroup($actionGroup);
                }

                break;
        }

        $this->addDefaultActions();
    }

    /**
     * Displays chosen flirt options for female and male patron.
     * @param "female"|"male" $patron
     * @param int $flirtOptionId
     * @return void
     */
    public function flirtAction(string $patron, int $flirtOptionId): void
    {
        $configPrefix = "{$patron}Patron";
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;
        $flirtOption = $config[$configPrefix]["flirts"][$flirtOptionId] ?? null;
        $maxCharm = $config["maxCharmPoints"] ?? 25;
        $minCharm = $config["minCharmPoints"] ?? 0;
        $characterCharm = $this->charmHandler->getCharm($this->character);
        $canLoseCharm = $characterCharm > $minCharm;
        $canGainCharm = $characterCharm < $maxCharm;

        // Error message in case the flirt option wasn't found
        // This should usually not happen. It can happen if the flirt option is removed from the config while
        // the scene is active.
        if (is_null($flirtOption)) {
            $this->logger->critical("Flirt option for {$patron} patron disappeared (scene: {$this->scene->id}, flirt option: {$flirtOptionId})", context: [
                $config,
                $configPrefix,
            ]);
            $this->stage->paragraphs = [
                new Paragraph(
                    id: self::Paragraphs["{$patron}Banter"],
                    text: "You walk a few steps. Suddenly, you forgot what you where about to do.",
                    context: $this->getDefaultContext(),
                )
            ];

            // Early break
            return;
        }

        // Prepare messages and charm changes
        if ($this->isFlirtSuccessful($this->character, $flirtOption)) {
            $paragraph = $flirtOption->successMessage;
            $charmChange = $flirtOption->addCharmIfSuccessful ? 1 : 0;
            $turnChange = $flirtOption->isExhaustiveIfSuccessful ? 2 : 0;
        } else {
            $paragraph = $flirtOption->failureMessage;
            $charmChange = $flirtOption->removeCharmIfFailure ? -1 : 0;
            $turnChange = 0;
        }

        $this->stage->paragraphs = [
            new Paragraph(
                id: self::Paragraphs["femaleBanter"],
                text: $paragraph,
                context: [
                    ... $this->getDefaultContext(),
                    "consumedTurns" => 0,
                    "rewardedCharm" => 0,
                ],
            )
        ];

        // Only remove or add charm if the flirt option allows it
        // And only add charm if the character's charm is within the reward or removal range
        if (
            (
                $charmChange < 0
                and $canLoseCharm
                and (
                    $flirtOption->charmRemovalRange === null
                    or $flirtOption->charmRemovalRange->isWithin($characterCharm)
                )
            ) or (
                $charmChange > 0
                and $canGainCharm
                and (
                    $flirtOption->charmRewardRange === null
                    or $flirtOption->charmRewardRange->isWithin($characterCharm)
                )
            )
        ) {
            $this->charmHandler->addCharm($this->character, $charmChange);

            // @phpstan-ignore offsetAccess.notFound
            $this->stage->paragraphs[self::Paragraphs["femaleBanter"]]->addContext("rewardedCharm", $charmChange);
        }

        if ($turnChange > 0) {
            if ($this->healthHandler->getTurns() > 0) {
                $this->healthHandler->addTurns(-$turnChange, $this->character);

                // @phpstan-ignore offsetAccess.notFound
                $this->stage->paragraphs[self::Paragraphs["femaleBanter"]]->addContext("consumedTurns", $turnChange);
            }
        }

        $this->setSeenLover($this->character);
    }

    /**
     * Returns the default context for the stage's paragraph.
     * @return InnTemplateDefaultContext
     */
    public function getDefaultContext(): array
    {
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;

        // Determine random banter
        $banterList = explode(",", $config["innKeeperBanter"] ?? "dragons");
        $banter = $this->diceBag->pick($banterList)[0] ?? "dragons";

        return [
            "patron" => [
                "male" => $config["malePatron"]["name"] ?? "Seth",
                "female" => $config["femalePatron"]["name"] ?? "Violet",
            ],
            "patronBanter" => [
                "male" => $config["malePatron"]["comment"] ?? "",
                "female" => $config["femalePatron"]["comment"] ?? "",
            ],
            "innKeeper" => $config["innKeeper"]["name"] ?? "Cedrik",
            "innKeeperBanter" => $banter,
        ];
    }

    /**
     * Adds default actions to the stage.
     * @return void
     */
    public function addDefaultActions(): void
    {
        /** @var InnTemplateConfiguration $config */
        $config = $this->scene->templateConfig;
        $innKeeper = $config['innKeeper']["name"]??'Cedrik';
        $malePatron = $config['malePatron']["name"] ?? "Seth";
        $femalePatron = $config['femalePatron']["name"] ?? "Violet";

        $actionGroup = new ActionGroup(
            id: self::ActionGroup["chat"],
            title: "Things to do",
            actions: [
                new Action(
                    scene: $this->scene,
                    title: ($this->genderHandler->prefersFemale($this->character) ? 'Flirt with ' : 'Chat with ') . $femalePatron,
                    parameters: ["op" => "femalePatron"],
                    reference: self::Action["femalePatron"],
                ),
                new Action(
                    scene: $this->scene,
                    title: "Talk to {$malePatron} the Bard",
                    parameters: ["op" => "malePatron"],
                    reference: self::Action["malePatron"],
                ),
                new Action(
                    scene: $this->scene,
                    title: "Converse with patrons",
                    parameters: ["op" => "otherPatrons"],
                    reference: self::Action["otherPatrons"],
                ),
                new Action(
                    scene: $this->scene,
                    title: "Talk to {$innKeeper} the Barkeep",
                    parameters: ["op" => "innKeeper"],
                    reference: self::Action["innKeeper"],
                ),
            ]
        );

        $this->stage->addActionGroup($actionGroup);
    }

    /**
     * Calculates the chance of a flirt being successful and returns true if it was successful.
     * @param Character $character
     * @param InnFlirtOption $flirtOption
     * @return bool
     */
    public function isFlirtSuccessful(Character $character, InnFlirtOption $flirtOption): bool
    {
        $characterCharm = $this->charmHandler->getCharm($character);
        $requiredCharm = $flirtOption->charmRequirement;
        $deltaCharm = $requiredCharm - $characterCharm;

        $this->logger->debug("Check if flirt is successful. Character charm: {$characterCharm}, required charm: {$requiredCharm}, delta charm: {$deltaCharm}");

        if ($deltaCharm <= 0) {
            // Character has more or equal charm than required. This is enough to pass the charm check.
            return true;
        }

        $chance = 1/$deltaCharm/2;
        return $this->diceBag->chance($chance, precision: 5);
    }

    /**
     * Returns true of the character has seen the inn's lover.
     * @param Character $character
     * @return bool
     */
    public function getSeenLover(Character $character): bool
    {
        return $character->getProperty(self::SeenLoverProperty, false) ?? false;
    }

    /**
     * Set whether the character has seen the inn's lover.
     * @param Character $character
     * @param bool $seen
     * @return void
     */
    public function setSeenLover(Character $character, bool $seen = true): void
    {
        $character->setProperty(self::SeenLoverProperty, $seen);
    }

    /**
     * @param Character $character
     * @return int<0, max> Drunkenness level of the character
     */
    public function getDrunkenness(Character $character): int
    {
        return (int)($character->getProperty(self::DrunkenessProperty, 0) ?? 0);
    }

    /**
     * @param Character $character
     * @param int<0, max> $drunkenness Drunkenness level of the character
     * @return void
     */
    public function setDrunkenness(Character $character, int $drunkenness): void
    {
        $this->logger->debug("{$character}: Drunkenness set to {$drunkenness}.");
        $character->setProperty(self::DrunkenessProperty, $drunkenness);
    }

    public function addDrunkenness(Character $character, int $drunkenness): void
    {
        $this->setDrunkenness($character, $this->getDrunkenness($character) + $drunkenness);
    }

    #[AsEventListener(event: NewDay::OnNewDayAfter, priority: -10)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        if ($this->getDrunkenness($event->character) > 66) {
            $this->healthHandler->addTurns(-1, $event->character);
            $event->stage->addParagraph(new Paragraph(
                id: self::Paragraphs["hangover"],
                text: "You wake up with a hangover.",
            ));
        }

        $this->setSeenLover($event->character, false);
        $this->setDrunkenness($event->character, 0);
    }



    /**
     * @param FormExtensionEvent<GameSettingsDataType> $event
     * @return void
     */
    #[AsEventListener(event: GameSettings::FormExtensionEventName)]
    public function onGameSettingsFormExtension(FormExtensionEvent $event): void
    {
        $event->builder->add($event->builder
            ->create(self::GameSettingProperty, GroupedFormType::class, [
                "label" => "Alcohol",
            ])
            ->add("allowAlcohol", CheckboxType::class, [
                "label" => "Allow Alcohol",
                "help" => "Whether alcohol can be consumed (at all).",
                "data" => true,
                "required" => false,
            ])
            ->add("hangOverLimit", IntegerType::class, [
                "label" => "Hangover Limit",
                "help" => "The maximum drunkenness level before a hangover message is shown",
                "constraints" => [
                    new Range(min: 0),
                    new NotBlank(),
                ],
                "data" => 67,
            ])
        );
    }
}