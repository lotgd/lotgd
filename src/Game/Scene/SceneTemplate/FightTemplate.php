<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\CreatureRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type FightTemplateConfiguration array{
 *     searchFightAction: string,
 *     searchSlummingAction: string,
 *     searchThrillseekingAction: string,
 * }
 * @implements SceneTemplateInterface<FightTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
readonly class FightTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;
    use DefaultFightTrait;

    const string ActionGroupSearch = "lotgd2.actionGroup.fightTemplate.search";

    public function __construct(
        private Security $security,
        private LoggerInterface $logger,
        private AttachmentRepository $attachmentRepository,
        private Stats $experience,
        private DiceBagInterface $diceBag,
        private CreatureRepository $creatureRepository,
        private Battle $battle,
        private Health $health,
        private Stats $stats,
        private Gold $gold,
    ) {
    }

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->define("searchFightAction")
            ->required()
            ->allowedTypes("string")
            ->default("Search for a fight");

        $resolver
            ->define("searchSlummingAction")
            ->required()
            ->allowedTypes("string")
            ->default("Go Slumming");

        $resolver
            ->define("searchThrillseekingAction")
            ->required()
            ->allowedTypes("string")
            ->default("Go Thrillseeking");

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameter("op");
        $this->logger->debug("Called FightTemplate::onSceneChange, op={$op}");

        if ($op === "cheat" and $this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $this->handleCheats($action->getParameter("what"));
        }

        match($op) {
            "search" => $this->searchAction($stage, $action, $scene),
            "fight" => $this->fightAction($stage, $action, $scene),
            default => $this->defaultAction($stage, $action, $scene),
        };
    }

    /**
     * Offers the default scene but adds navigation to pick battles.
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @return void
     */
    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called FightTemplate::defaultAction");

        if ($this->health->getHealth() <= 0) {
            $stage->addDescription("You are too tired to delve deeper into the woods.");
        }

        $this->addDefaultActions($stage, $action, $scene);
    }

    /**
     * Searches for a
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @return void
     */
    public function searchAction(Stage $stage, Action $action, Scene $scene): void
    {
        // Adjust level
        $level = intval($action->getParameter("level", 0));
        $this->logger->debug("Called FightTemplate::searchAction, with level={$level}");
        $level += $this->getRandomLevelChange();
        $level = $this->experience->getLevel() + $level;
        $this->logger->debug("FightTemplate::searchAction, level adjusted to {$level}");

        // Find creature
        $creature = $this->creatureRepository->getRandomCreature($level);

        if (!$creature) {
            $this->addDefaultActions($stage, $action, $scene);
            $stage->description = "This place looks very peaceful.";
            $this->logger->critical("Character {$stage->owner->id} did not find any creatures");
            return;
        }

        $this->logger->debug("Found a creature to battle. {$creature->name}, level: {$creature->level}, health: {$creature->health}");

        // Find attachment
        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        if ($attachment) {
            $battleState = $this->battle->start($creature);

            $stage->addAttachment($attachment, data: [
                "battleState" => $battleState,
            ]);

            $params = ["op" => "fight"];

            if ($this->diceBag->chance(0.25)) {
                // Player is getting surprised
                $stage->description = <<<TEXT
                You walk through the forest, looking for a monster to fight against. Suddenly, a <.{{creatureName}}.> appears, ready to fight you with its weapon <.{{creatureWeapon}}.>.
                TEXT;

            } else {
                $stage->description = <<<TEXT
                You walk through the forest, looking for a monster to fight against. After a while, you encounter a <.{{creatureName}}.>, wielding its weapon <.{{creatureWeapon}}.>.
                
                It has not yet spotted you, allowing you a surprise attack
                TEXT;

                $params["surprise"] = true;
            }

            $stage->clearActionGroups();
            $this->battle->addFightActions($stage, $scene, $battleState, $params);

            $stage->addContext("creatureName", $creature->name);
            $stage->addContext("creatureWeapon", $creature->weapon);
        } else {
            $stage->description = "You are too blind to see any monsters. Maybe prey to the gods and ask for why that is?";
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");

            $this->addDefaultActions($stage, $action, $scene);
        }
    }

    /**
     * Adds the actions required to search for a fight. The level action parameter defines the difficulty.
     * @param Stage $stage
     * @param Scene $scene
     * @return void
     */
    public function addDefaultActions(Stage $stage, Action $action, Scene $scene): void
    {
        if ($this->health->isAlive()) {
            $actionGroup = new ActionGroup(self::ActionGroupSearch, $scene->title);

            $actionGroup->addAction(
                new Action(
                    scene: $scene,
                    title: $scene->templateConfig["searchFightAction"],
                    parameters: [
                        "op" => "search",
                        "level" => 0,
                    ]
                )
            );

            // Only allow searching for easy battles if level is larger than 1. Enemies can only be level 1 or higher, so
            //  it wouldn't make sense to offer this option on level 1.
            if ($stage->owner->level > 1) {
                $actionGroup->addAction(
                    new Action(
                        scene: $scene,
                        title: $scene->templateConfig["searchSlummingAction"] ?? "Go slumming",
                        parameters: [
                            "op" => "search",
                            "level" => -1,
                        ]
                    )
                );
            }

            $actionGroup->addAction(
                new Action(
                    scene: $scene,
                    title: $scene->templateConfig["searchThrillseekingAction"] ?? "Go thrillseeking",
                    parameters: [
                        "op" => "search",
                        "level" => 1,
                    ]
                )
            );

            $stage->addActionGroup($actionGroup);
        }

        if ($this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $cheatsGroup = new ActionGroup("lotgd2.actionGroup.fightTemplate.cheats", "Cheats");
            $cheatsGroup->setActions([
                new Action(
                    scene: $scene,
                    title: "#! Gain 1000 experience",
                    parameters: ["op" => "cheat", "what" => "experience"],
                    reference: "lotgd2.action.fightTemplate.cheats.experience",
                ),
                new Action(
                    scene: $scene,
                    title: "#! Gain 1000 gold",
                    parameters: ["op" => "cheat", "what" => "gold"],
                    reference: "lotgd2.action.fightTemplate.cheats.gold",)
            ]);
            $stage->addActionGroup($cheatsGroup);
        }
    }

    /**
     * Returns a level modifier to get easier or more difficult battles
     * @return int<-1, 1>
     */
    public function getRandomLevelChange(): int
    {
        $level = 0;
        // The original code only modifies the level if e_rand(0,2)==1
        // With e_rand(), the two extremes are roughly half as likely as the others.
        // That would put e_rand(0, 2) as the same as a 50% chance.

        // Then, a level increment is done if e_rand(1,5)==1
        //  This is a 25% chance for 2, 3 and 4, and 12.5% for 1 and 5, making this effectively a 12.5% chance
        $level += $this->diceBag->chance(0.125, 4) ? 1 : 0;

        // At the same time, a negative level increment is done if e_rand(1,3)==1
        // This is a 50% chance for 2, and a 25% chance for 1 and 3, making this effectively a 25% chance
        $level -= $this->diceBag->chance(0.25, 4) ? 1 : 0;

        // There is are, effectively, 3 outcomes here:
        //  -  5.47% chance for a positive increase (P(plev) * (1-P(nlev))
        //  - 11.71% chance for a negative increase ((1-P(plev) * P(nlev))
        //  - 82.81% chance for no change (1 - P(plev) * (1-P(nlev) - (1-P(plev) * P(nlev))
        return $level;
    }

    public function handleCheats(string $cheat): void
    {
        if ($cheat === "experience") {
            $this->stats->addExperience(1000);
        } elseif ($cheat === "gold") {
            $this->gold->addGold(1000);
        }
    }
}