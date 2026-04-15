<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Form\Scene\SceneTemplate\TrainingTemplateType;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\MasterRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-type TrainingTemplateConfiguration array{
 *     campLeader: string,
 *     text: array{
 *         maxLevelReached: string,
 *         askExperience: string,
 *         seenMaster: string,
 *         absoluteDefeat: string,
 *     }
 * }
 * @implements SceneTemplateInterface<TrainingTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(TrainingTemplateType::class)]
class TrainingTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;
    use DefaultFightTrait;

    const string ActionGroupTraining = "lotgd2.actionGroup.trainingTemplate.training";
    const string ActionQuestion = "lotgd2.action.trainingTemplate.question";
    const string ActionChallenge = "lotgd2.action.trainingTemplate.challenge";
    const string OnCharacterLevelUp = 'lotgd2.event.TrainingTemplate.levelUp';

    public function __construct(
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly MasterRepository $masterRepository,
        private readonly DiceBagInterface $diceBag,
        private readonly Battle $battle,
        private readonly EquipmentHandler $equipment,
        private readonly StatsHandler $stats,
        private readonly HealthHandler $health,
        private readonly GoldHandler $gold, // @phpstan-ignore property.onlyWritten
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            "experience" => $this->stats->getExperience(),
            "requiredExperience" => $this->stats->getRequiredExperience(),
            "weapon" => $this->equipment->getItemInSlot(EquipmentHandler::WeaponSlot)?->getName() ?? "Fists",
            "armor" => $this->equipment->getItemInSlot(EquipmentHandler::ArmorSlot)?->getName() ?? "T-Shirt",
        ];
    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameters()["op"] ?? "";
        $this->logger->debug("Called TrainingTemplate::onSceneChange, op={$op}");

        if ($op === "cheat" and $this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $this->handleCheats($this->stage->owner, $this->action->getParameter("what"));
        }

        match ($op) {
            default => $this->defaultAction(),
            "ask" => $this->askAction(),
            "challenge" => $this->challengeAction(),
            "fight" => $this->fightAction(),
        };
    }

    public function defaultAction(): void
    {
        $master = $this->masterRepository->getByLevel($this->character->level);

        // If this returns null, the max level has been reached.
        if ($master === null) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.trainingTemplate.maxLevelReached",
                    text: $this->scene->templateConfig["text"]["maxLevelReached"],
                    context: [
                        ... $this->getContext(),
                        "campLeader" => $this->scene->templateConfig["campLeader"],
                    ]
                )
            ];
        } else {
            $this->addDefaultActions();

            $sceneParagraph = $this->stage->paragraphs[Stage::SceneText] ?? null;
            $sceneParagraph->addContext("master", $master->name);

            $context = $this->getContext();
            array_walk($context, function ($value, $key) use ($sceneParagraph) {
                $sceneParagraph->addContext($key, $value);
            });
        }
    }

    public function askAction(): void
    {
        $master = $this->masterRepository->getByLevel($this->character->level);

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.trainingTemplate.askExperience",
                text: $this->scene->templateConfig["text"]["askExperience"],
                context: [
                    ... $this->getContext(),
                    "campLeader" => $this->scene->templateConfig["campLeader"],
                    "master" => $master,
                ]
            )
        ];

        $this->addDefaultActions();
    }

    public function challengeAction(): void
    {
        $character = $this->stage->owner;
        $master = $this->masterRepository->getByLevel($character->level);

        if ($this->getSeenMaster($character)) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.trainingTemplate.seenMaster",
                    text: $this->scene->templateConfig["text"]["seenMaster"],
                    context: [
                        ... $this->getContext(),
                        "campLeader" => $this->scene->templateConfig["campLeader"],
                        "master" => $master,
                    ]
                )
            ];
        } elseif ($this->stats->getExperience() < $this->stats->getRequiredExperience()) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.trainingTemplate.absoluteDefeat",
                    text: $this->scene->templateConfig["text"]["absoluteDefeat"],
                    context: [
                        ... $this->getContext(),
                        "campLeader" => $this->scene->templateConfig["campLeader"],
                        "master" => $master,
                    ]
                )
            ];

            $this->setSeenMaster($character);
        } elseif($master) {
            // Find attachment
            $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

            if ($attachment) {
                $battleState = $this->battle->start($master, allowFlee: false);
                $params = ["op" => "fight"];

                $healed = false;
                if ($this->health->getHealth() < $this->health->getMaxHealth()) {
                    $this->health->heal();
                    $healed = true;
                }

                $this->stage->paragraphs = [
                    new Paragraph(
                        id: "lotgd2.paragraph.trainingTemplate.fightStarted",
                        text: <<<TEXT
                            {% if healed %} {{ master.name }} offers you a healing potion before the fight. You are back to 
                            full health. {% endif %}
                            
                            You ready your {{ weapon }} and {{ armor }} and bow to {{ master.name }}. He bows, too, and draws 
                            his {{ master.weapon }}.
                            TEXT,
                        context: [
                            ... $this->getContext(),
                            "healed" => $healed,
                            "campLeader" => $this->scene->templateConfig["campLeader"],
                            "master" => $master,
                        ]
                    )
                ];

                $this->stage->actionGroups = [];

                $this->battle->addFightActions($this->stage, $this->scene, $battleState, $params);
            } else {
                $this->stage->paragraphs = [
                    new Paragraph(
                        id: "lotgd2.paragraph.trainingTemplate.attachmentDisappeared",
                        text: "Your maser suddenly sheats his weapon and disappears, his intentions unclear. 
                            Maybe prey to the gods and ask for why that was?",
                    )
                ];

                $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
            }
        } else {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.trainingTemplate.maxLevelReached",
                    text: $this->scene->templateConfig["text"]["maxLevelReached"],
                    context: [
                        ... $this->getContext(),
                        "campLeader" => $this->scene->templateConfig["campLeader"],
                    ]
                )
            ];
        }
    }

    /**
     * Overwrites the default onFightWon method.
     *
     * When a master has been defeated, no gold or experience is gained - instead, the level is increased.
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(BattleState $battleState): void
    {
        $this->levelUp();

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.trainingTemplate.maxLevelReached",
                text: <<<TEXT
                    You have defeated your master, <.{{ badGuy.name }}.>. {% if textDefeated %}<<{{ textDefeated }}>>{% endif %}
                    
                    You level up! You are now on level {{ character.level }}.
                    
                    Your max health is now {{ maxHealth }}. Your attack and defense both increase by one.
                    
                    {% if character.level < 15 %}
                        You now have a new master.
                    {% else %}
                        Nobody is stronger than you.
                    {% endif %}
                    TEXT,
                context: [
                    "campLeader" => $this->scene->templateConfig["campLeader"],
                    "badGuy" => $battleState->badGuy,
                    "maxHealth" => $this->health->getMaxHealth(),
                ]
            )
        ];

        $this->setSeenMaster($this->character, false);

        $this->logger->debug("Character {$this->character->id} won against his master and is now on level {$this->character->level}.");
    }

    /**
     * Overwrites the default onFightList method.
     *
     * When a fight against a master is lost, the character doesn't die, but gets healed.
     * @param BattleState $battleState
     * @return void
     */
    public function onFightLost(BattleState $battleState): void
    {
        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.trainingTemplate.maxLevelReached",
                text: <<<TEXT
                    You have been defeated by <.{{ badGuy.name }}.>. They halt just before delivering the final blow, and instead 
                    extend a hand to help you to your feet, and hand you a complementary healing potion.
                    
                    {% if textLost %}<<{{ textLost }}>>{% endif %}
                    TEXT,
                context: [
                    "campLeader" => $this->scene->templateConfig["campLeader"],
                    "badGuy" => $battleState->badGuy,
                    "maxHealth" => $this->health->getMaxHealth(),
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            )
        ];

        $this->health->heal();
        $this->setSeenMaster($this->stage->owner);
    }

    public function getSeenMaster(Character $character): bool
    {
        return $character->getProperty("lotgd2.trainingTemplate.seenMaster", false);
    }

    public function setSeenMaster(Character $character, bool $seenMaster = true): void
    {
        $character->setProperty("lotgd2.trainingTemplate.seenMaster", $seenMaster);
    }

    public function addDefaultActions(): void
    {
        $actionGroup = new ActionGroup(self::ActionGroupTraining, "Training");
        $actionGroup->addAction(new Action(
            $this->scene,
            title: "Question Master",
            parameters: ["op" => "ask"],
            reference: self::ActionQuestion,
        ));
        $actionGroup->addAction(new Action(
            $this->scene,
            title: "Challenge Master",
            parameters: ["op" => "challenge"],
            reference: self::ActionChallenge,
        ));

        $this->stage->addActionGroup($actionGroup);


        if ($this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $cheatsGroup = new ActionGroup("lotgd2.actionGroup.fightTemplate.cheats", "Cheats");
            $cheatsGroup->setActions([
                new Action(
                    scene: $this->scene,
                    title: "#! Unsee master",
                    parameters: ["op" => "cheat", "what" => "unseeMaster"],
                    reference: "lotgd2.action.fightTemplate.cheats.unseeMaster",
                ),
                new Action(
                    scene: $this->scene,
                    title: "#! Gain 1 level",
                    parameters: ["op" => "cheat", "what" => "levelUp"],
                    reference: "lotgd2.action.fightTemplate.cheats.levelUp",
                ),
                new Action(
                    scene: $this->scene,
                    title: "#! Set level to 15",
                    parameters: ["op" => "cheat", "what" => "level15"],
                    reference: "lotgd2.action.fightTemplate.cheats.level15",
                ),
            ]);

            $this->stage->addActionGroup($cheatsGroup);
        }
    }

    public function handleCheats(Character $character, string $cheat): void
    {
        if ($cheat === "unseeMaster") {
            $this->setSeenMaster($character);
        } elseif ($cheat === "levelUp") {
            $this->levelUp();
        } elseif ($cheat === "level15") {
            $this->levelUp(15);
        }
    }

    /**
     * @return void
     */
    public function levelUp(?int $targetLevel = null): void
    {
        $oldCharacter = clone $this->character;

        if ($targetLevel === null) {
            $level = 1;
        } else {
            $level = $targetLevel - $this->character->level;
        }

        $this->character->level += $level;
        $this->logger->debug("{$this->character}: Level increased to {$this->character->level}.");
        $event = new CharacterChangeEvent($this->character, $oldCharacter);

        $this->eventDispatcher->dispatch($event, self::OnCharacterLevelUp);
    }
}