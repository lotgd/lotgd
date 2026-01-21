<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\MasterRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
readonly class TrainingTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;
    use DefaultFightTrait;

    const ActionGroupTraining = "lotgd2.actionGroup.trainingTemplate.training";
    const ActionQuestion = "lotgd2.action.trainingTemplate.question";
    const ActionChallenge = "lotgd2.action.trainingTemplate.challenge";

    public function __construct(
        private LoggerInterface $logger,
        private AttachmentRepository $attachmentRepository,
        private MasterRepository $masterRepository,
        private ActionService $actionService,
        private Battle $battle,
        private Equipment $equipment,
        private Stats $stats,
        private Health $health,
    ) {
    }

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();

        $resolver->define("campLeader")->allowedTypes("string")->default("Bluspring");

        $resolver
            ->define("text")
            ->default(function (OptionsResolver $resolver) {
                $resolver
                    ->define("maxLevelReached")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("askExperience")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("seenMaster")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("absoluteDefeat")
                    ->required()
                    ->allowedTypes('string');
            });

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameters()["op"] ?? "";
        $this->logger->debug("Called TrainingTemplate::onSceneChange, op={$op}");

        $stage->addContext("experience", $this->stats->getExperience());
        $stage->addContext("requiredExperience", $this->stats->getRequiredExperience());
        $stage->addContext("weapon", $this->equipment->getItemInSlot(Equipment::WeaponSlot)?->getName() ?? "Fists");
        $stage->addContext("armor", $this->equipment->getItemInSlot(Equipment::ArmorSlot)?->getName() ?? "T-Shirt");

        match ($op) {
            default => $this->defaultAction($stage, $action, $scene),
            "ask" => $this->askAction($stage, $action, $scene),
            "challenge" => $this->challengeAction($stage, $action, $scene),
            "fight" => $this->fightAction($stage, $action, $scene),
        };
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $character = $stage->getOwner();
        $master = $this->masterRepository->getByLevel($character->getLevel());

        // If this returns null, the max level has been reached.
        if ($master === null) {
            $stage->setDescription(
                $scene->getTemplateConfig()["text"]["maxLevelReached"],
            );

            $stage->setContext([
                "campLeader" => $scene->getTemplateConfig()["campLeader"],
            ]);
        } else {
            $this->addDefaultActions($stage, $action, $scene);
            $stage->addContext("master", $master->name);
        }
    }

    public function askAction(Stage $stage, Action $action, Scene $scene): void
    {
        $character = $stage->getOwner();
        $master = $this->masterRepository->getByLevel($character->getLevel());

        $stage
            ->setDescription(
                $scene->getTemplateConfig()["text"]["askExperience"],
            )
            ->setContext([
                "campLeader" => $scene->getTemplateConfig()["campLeader"],
                "master" => $master,
                "experience" => $this->stats->getExperience(),
                "requiredExperience" => $this->stats->getRequiredExperience(),
            ])
        ;

        $this->addDefaultActions($stage, $action, $scene);
    }

    public function challengeAction(Stage $stage, Action $action, Scene $scene): void
    {
        $character = $stage->getOwner();
        $master = $this->masterRepository->getByLevel($character->getLevel());

        if ($this->getSeenMaster($character)) {
            $stage
                ->setDescription(
                    $scene->getTemplateConfig()["text"]["seenMaster"],
                )
                ->setContext([
                    "campLeader" => $scene->getTemplateConfig()["campLeader"],
                    "master" => $master,
                    "weapon" => $this->equipment->getName(Equipment::WeaponSlot),
                    "armor" => $this->equipment->getName(Equipment::WeaponSlot),
                ])
            ;
        } elseif ($this->stats->getExperience() < $this->stats->getRequiredExperience()) {
            $stage
                ->setDescription(
                    $scene->getTemplateConfig()["text"]["absoluteDefeat"],
                )
                ->setContext([
                    "campLeader" => $scene->getTemplateConfig()["campLeader"],
                    "master" => $master,
                    "weapon" => $this->equipment->getName(Equipment::WeaponSlot),
                    "armor" => $this->equipment->getName(Equipment::ArmorSlot),
                ])
            ;

            $this->setSeenMaster($character);
        } else {
            // Find attachment
            $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

            if ($attachment) {
                $battleState = $this->battle->start($master);
                $params = ["op" => "fight"];

                $healed = false;
                if ($this->health->getHealth() < $this->health->getMaxHealth()) {
                    $this->health->heal();
                    $healed = true;
                }

                $stage
                    ->setDescription(<<<TEXT
                        {% if healed %} {{ master.name }} offers you a healing potion before the fight. You are back to 
                        full health. {% endif %}
                        
                        You ready your {{ weapon }} and {{ armor }} and bow to {{ master.name }}. He bows, too, and draws 
                        his {{ master.weapon }}.
                    TEXT)
                    ->setContext([
                        "weapon" => $this->equipment->getName(Equipment::WeaponSlot),
                        "armor" => $this->equipment->getName(Equipment::ArmorSlot),
                        "master" => $master,
                        "healed" => $healed,
                    ])
                    ->clearActionGroups()
                ;

                $this->battle->addFightActions($stage, $scene, $battleState, $params);
            } else {
                $stage->setDescription("Your maser suddenly sheats his weapon and disappears, his intentions unclear. 
                    Maybe prey to the gods and ask for why that was?");
                $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
            }
        }
    }

    /**
     * Overwrites the default onFightWon method.
     *
     * When a master has been defeated, no gold or experience is gained - instead, the level is increased.
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        $character = $stage->getOwner();

        $this->stats->levelUp();
        $this->health->heal();

        $stage
            ->setDescription(<<<TEXT
                You have defeated your master, <.{{ badGuy.name }}.>. {% if textDefeated %}<<{{ textDefeated }}>>{% endif %}
                
                You level up! You are now on level {{ character.level }}.
                
                Your max health is now {{ maxHealth }}. Your attack and defense both increase by one.
                
                {% if character.level < 15 %}
                    You now have a new master.
                 {% else %}
                    Nobody is stronger than you.
                 {% endif %}
                TEXT
            )
            ->addContext("maxHealth", $this->health->getMaxHealth())
        ;

        $this->setSeenMaster($character, false);

        $this->logger->debug("Character {$character->getId()} won against his master and is now on level {$character->getLevel()}.");
    }

    /**
     * Overwrites the default onFightList method.
     *
     * When a fight against a master is lost, the character doesn't die, but gets healed.
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @param BattleState $battleState
     * @return void
     */
    public function onFightLost(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        $stage->setDescription(<<<TEXT
            You have been defeated by <.{{ badguy.name }}.>. They halt just before delivering the final blow, and instead 
            extend a hand to help you to your feet, and hand you a complementary healing potion.
            
            {% if textLost %}<<{{ textLost }}>>{% endif %}
            TEXT
        );

        $this->health->heal();
        $this->setSeenMaster($stage->getOwner(), false);
    }

    public function getSeenMaster(Character $character): bool
    {
        return $character->getProperty("lotgd2.trainingTemplate.seenMaster", false);
    }

    public function setSeenMaster(Character $character, bool $seenMaster = true): void
    {
        $character->setProperty("lotgd2.trainingTemplate.seenMaster", $seenMaster);
    }

    public function addDefaultActions(Stage $stage, Action $action, Scene $scene): void
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
    }
}