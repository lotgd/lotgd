<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\SimpleStageParameterEvent;
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
    const string SeenMasterProperty = "lotgd2.trainingTemplate.seenMaster";
    const string ActionGroupCheats = "lotgd2.actionGroup.fightTemplate.cheats";
    const string ActionCheatUnseeMaster = "lotgd2.action.fightTemplate.cheats.unseeMaster";
    const string ActionCheatLevelUp = "lotgd2.action.fightTemplate.cheats.levelUp";
    const string ActionCheatSetLevelTo15 = "lotgd2.action.fightTemplate.cheats.level15";

    public function __construct(
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly MasterRepository $masterRepository,
        private readonly Battle $battle,
        private readonly EquipmentHandler $equipment,
        private readonly StatsHandler $stats,
        private readonly HealthHandler $health,
        private readonly GoldHandler $gold, // @phpstan-ignore property.onlyWritten
    ) {
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function getScene(): Scene
    {
        return $this->scene;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            "experience" => $this->stats->getExperience($this->character),
            "requiredExperience" => $this->stats->getRequiredExperience($this->character),
            "weapon" => $this->equipment->getItemInSlot(EquipmentHandler::WeaponSlot, $this->character)?->getName() ?? "Fists",
            "armor" => $this->equipment->getItemInSlot(EquipmentHandler::ArmorSlot, $this->character)?->getName() ?? "T-Shirt",
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
        };
    }

    public function defaultAction(): void
    {
        if ($this->health->isAlive() === false) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.trainingTemplate.isDead",
                    text: "You are dead. Dead people cannot challenge their master.",
                    context: [],
                )
            ];

            return;
        }

        $master = $this->masterRepository->getByLevel($this->character->level);

        // If there is no master left, the max level has been reached. Or we just assume so.
        //  to actually increase the max level, it requires more effort than just adding another master though.
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
            $this->addDefaultActions($this->stage, $this->scene);

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

        $this->addDefaultActions($this->stage, $this->scene);
    }

    public function challengeAction(): void
    {
        $character = $this->character;
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
        } elseif ($this->stats->getExperience($character) < $this->stats->getRequiredExperience($character)) {
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
            $attachment = $this->attachmentRepository->findOneBy(["attachmentClass" => BattleAttachment::class]);

            if ($attachment) {
                $battleState = $this->battle->start($master, allowFlee: false);
                $params = ["op" => "fight"];

                $healed = false;

                if ($this->health->getHealth($character) < $this->health->getMaxHealth($character)) {
                    $this->health->heal(character: $character);
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
     * @param SimpleStageParameterEvent $event
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(SimpleStageParameterEvent $event, BattleState $battleState): void
    {
        $this->levelUp($event->character, stage: $event->stage);

        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.trainingTemplate.onFightWon",
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
                    "campLeader" => $event->scene->templateConfig["campLeader"],
                    "badGuy" => $battleState->badGuy,
                    "maxHealth" => $this->health->getMaxHealth(),
                ]
            )
        ];

        $this->setSeenMaster($event->character, false);
        $this->logger->debug("Character {$event->character->id} won against his master and is now on level {$event->character->level}.");
    }

    /**
     * Overwrites the default onFightList method.
     *
     * When a fight against a master is lost, the character doesn't die, but gets healed.
     * @param SimpleStageParameterEvent $event
     * @param BattleState $battleState
     * @return void
     */
    public function onFightLost(SimpleStageParameterEvent $event, BattleState $battleState): void
    {
        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.trainingTemplate.onFightLost",
                text: <<<TEXT
                    You have been defeated by <.{{ badGuy.name }}.>. They halt just before delivering the final blow, and instead 
                    extend a hand to help you to your feet, and hand you a complementary healing potion.
                    
                    {% if textLost %}<<{{ textLost }}>>{% endif %}
                    TEXT,
                context: [
                    "campLeader" => $event->scene->templateConfig["campLeader"],
                    "badGuy" => $battleState->badGuy,
                    "maxHealth" => $this->health->getMaxHealth(),
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            )
        ];

        $this->health->heal();
        $this->setSeenMaster($event->character);
        $this->logger->debug("Character {$event->character->id} lost against his master.");
    }

    public function getSeenMaster(Character $character): bool
    {
        return $character->getProperty(self::SeenMasterProperty, false);
    }

    public function setSeenMaster(Character $character, bool $seenMaster = true): void
    {
        $character->setProperty(self::SeenMasterProperty, $seenMaster);
    }

    public function addDefaultActions(Stage $stage, Scene $scene): void
    {
        $actionGroup = new ActionGroup(self::ActionGroupTraining, "Training");
        $actionGroup->addAction(new Action(
            $scene,
            title: "Question Master",
            parameters: ["op" => "ask"],
            reference: self::ActionQuestion,
        ));
        $actionGroup->addAction(new Action(
            $scene,
            title: "Challenge Master",
            parameters: ["op" => "challenge"],
            reference: self::ActionChallenge,
        ));

        $stage->addActionGroup($actionGroup);


        if ($this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $cheatsGroup = new ActionGroup(self::ActionGroupCheats, "Cheats");
            $cheatsGroup->setActions([
                new Action(
                    scene: $scene,
                    title: "#! Unsee master",
                    parameters: ["op" => "cheat", "what" => "unseeMaster"],
                    reference: self::ActionCheatUnseeMaster,
                ),
                new Action(
                    scene: $scene,
                    title: "#! Gain 1 level",
                    parameters: ["op" => "cheat", "what" => "levelUp"],
                    reference: self::ActionCheatLevelUp,
                ),
                new Action(
                    scene: $scene,
                    title: "#! Set level to 15",
                    parameters: ["op" => "cheat", "what" => "level15"],
                    reference: self::ActionCheatSetLevelTo15,
                ),
            ]);

            $stage->addActionGroup($cheatsGroup);
        }
    }

    /**
     * Handles cheats for this template.
     *
     * Available cheats are:
     *   unseeMaster: Sets seenMaster to false
     *   levelUp: Immediately calls the levelUp method
     *   level15: Immediately calls the levelUp method with target level 15
     * @param Character $character
     * @param string $cheat
     * @return void
     */
    public function handleCheats(Character $character, string $cheat): void
    {
        if ($cheat === "unseeMaster") {
            $this->setSeenMaster($character, false);
        } elseif ($cheat === "levelUp") {
            $this->levelUp($character);
        } elseif ($cheat === "level15") {
            $this->levelUp($character, 15);
        }
    }

    /**
     * @param Character $character
     * @param int|null $targetLevel
     * @param Stage|null $stage
     * @return void
     */
    public function levelUp(Character $character, ?int $targetLevel = null, ?Stage $stage = null): void
    {
        $oldCharacter = clone $character;

        if ($targetLevel === null) {
            $level = 1;
        } else {
            $level = $targetLevel - $character->level;
        }

        $character->level += $level;
        $this->logger->debug("{$character}: Level increased to {$character->level}.");

        $event = new CharacterChangeEvent($character, $oldCharacter, $stage);
        $this->eventDispatcher->dispatch($event, self::OnCharacterLevelUp);
    }
}