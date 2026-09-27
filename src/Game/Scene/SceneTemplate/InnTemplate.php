<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\Scene\SceneTemplate\InnTemplateType;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Handler\CharmHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @phpstan-type InnTemplateConfiguration array{
 *      innName: string,
 *      innKeeper: string,
 *      innKeeperBanter: string,
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
        "exhausted" => "lotgd2_paragraph_inn_exhausted",
    ];

    const string SeenLoverProperty = "lotgd2_property_innTemplate_seenMaster";

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DiceBagInterface $diceBag,
        private readonly GenderHandler $genderHandler,
        private readonly CharmHandler $charmHandler,
        private readonly HealthHandler $healthHandler,
    ) {

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
            "innKeeper" => $config["innKeeper"] ?? "Cedrik",
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
        $innKeeper = $config['innKeeper']??'Cedrik';
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

    #[AsEventListener(event: NewDay::OnNewDayAfter)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $this->setSeenLover($event->character, false);
    }
}