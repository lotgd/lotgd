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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Autoconfigure(public: true)]
readonly class FightTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    public function __construct(
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

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameter("op");
        $this->logger->debug("Called FightTemplate::onSceneChange, op={$op}");

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
        $this->logger->debug("Called BankTemplate::defaultAction");
        $this->addSearchNavigation($stage, $scene);
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
        $this->logger->debug("Called BankTemplate::searchAction, with level={$level}");
        $level += $this->getRandomLevelChange();
        $level = $this->experience->getLevel() + $level;
        $this->logger->debug("BankTemplate::searchAction, level adjusted to {$level}");

        // Find creature
        $creature = $this->creatureRepository->getRandomCreature($level);

        if (!$creature) {
            $this->addSearchNavigation($stage, $scene);
            $stage->setDescription("This place looks very peaceful.");
            $this->logger->critical("Character {$stage->getOwner()->getId()} did not find any creatures");
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
                $stage->setDescription(<<<TEXT
                You walk through the forest, looking for a monster to fight against. Suddenly, a <.{{creatureName}}.> appears, ready to fight you with its weapon <.{{creatureWeapon}}.>.
                TEXT);

            } else {
                $stage->setDescription(<<<TEXT
                You walk through the forest, looking for a monster to fight against. After a while, you encounter a <.{{creatureName}}.>, wielding its weapon <.{{creatureWeapon}}.>.
                
                It has not yet spotted you, allowing you a surprise attack
                TEXT);

                $params["surprise"] = true;
            }

            $this->battle->addFightActions($stage, $scene, $battleState, $params);

            $stage->addContext("creatureName", $creature->name);
            $stage->addContext("creatureWeapon", $creature->weapon);
        } else {
            $stage->setDescription("You are too blind to see any monsters. Maybe prey to the gods and ask for why that is?");
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");

            $this->addSearchNavigation($stage, $scene);
        }
    }

    public function fightAction(Stage $stage, Action $action, Scene $scene): void
    {
        $how = $action->getParameter("how");
        $battleState = $action->getParameter("battleState");

        if (!$battleState instanceof BattleState) {
            $this->addSearchNavigation($stage, $scene);
            $stage->setDescription("The battle suddenly ended.");
            $this->logger->critical("The BattleState was not transferred correctly", $action->getParameters());
            return;
        }

        // Find attachment
        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        if ($attachment) {
            $stage->setDescription(<<<TEXT
                You are in the middle of the fight against <.{{ creatureName }}.>.
                
                
                TEXT);
            $stage->addContext("creatureName", $battleState->badGuy->name);

            if ($action->getParameter("surprise", false) === true) {
                $battleTurn = BattleTurn::DamageTurnGoodGuy;
            } else {
                $battleTurn = BattleTurn::DamageTurnBoth;
            }

            if ($how === "flee") {
                if ($action->getParameter("surprise", false) === true || $this->diceBag->chance(0.3333, precision: 4)) {
                    $this->logger->critical("Successfully escaped from the enemy.");

                    $stage->setDescription(<<<TEXT
                        You have successfully fled your opponent!
                        
                    TEXT . $scene->getDescription());

                    // Add standard navigation
                    $this->addSearchNavigation($stage, $scene);

                    return;
                } else {
                    // Fleeing failed - meaning only the enemy gets to attack
                    $battleTurn = BattleTurn::DamageTurnBadGuy;

                    $stage->setDescription(<<<TEXT
                        You failed to flee your opponent! You are too busy trying to run away like a cowardly dog to try to fight.
                    TEXT);
                }
            }

            $stage->addAttachment($attachment, data: [
                "battleState" => $battleState,
            ]);

            $this->battle->fightOneRound($battleState, $battleTurn);

            if ($battleState->isOver()) {
                $this->processEndOfBattle($stage, $scene, $battleState);
            } else {
                // Only add fight actions if the fight is not over
                $stage->clearActionGroups();
                $this->battle->addFightActions($stage, $scene, $battleState, ["op" => "fight"]);
            }
        } else {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
        }
    }

    /**
     * Adds the actions required to search for a fight. The level action parameter defines the difficulty.
     * @param Stage $stage
     * @param Scene $scene
     * @return void
     */
    public function addSearchNavigation(Stage $stage, Scene $scene): void
    {
        $actionGroup = new ActionGroup("lotgd2.actionGroup.fightTemplate.search", $scene->getTitle());

        $searchAction = new Action(
            scene: $scene,
            title: $scene->getTemplateConfig()["searchFightAction"],
            parameters: [
                "op" => "search",
                "level" => 0,
            ]);

        $actionGroup->addAction($searchAction);
        $stage->addActionGroup($actionGroup);
    }

    /**
     * Processes the end of the battle. Distributes experience, gold, etc
     * @param Stage $stage
     * @param Scene $scene
     * @param BattleState $battleState
     * @return void
     */
    public function processEndOfBattle(Stage $stage, Scene $scene, BattleState $battleState): void
    {
        $stage->addContext("textDefeated", $battleState->badGuy->kwargs["textDefeated"] ?? null);
        $stage->addContext("textLost", $battleState->badGuy->kwargs["textLost"] ?? null);

        if ($battleState->result === BattleStateStatusEnum::GoodGuyWon) {
            // Calculate how much gold to drop
            $gold = $this->diceBag->pseudoBell(0, $battleState->badGuy->kwargs["gold"] ?? 1);
            $stage->addContext("gold", $gold);
            $this->gold->addGold($gold);

            // Calculate how much experience to earn
            $experience = $battleState->badGuy->kwargs["experience"] ?? 1;
            // Add a bit of variation
            $expFlux = (int)round($experience / 10);
            $experience += (int)round($this->diceBag->bell(-$expFlux, $experience));
            // Add level difference bonus
            $expBonus = max(0, (int)round($experience * (0.25 * ($battleState->badGuy->level - $battleState->goodGuy->level)), 0));
            $experience += $expBonus;
            $stage->addContext("experience", $experience);
            $stage->addContext("bonusExperience", $expBonus);
            $this->stats->addExperience($experience);

            $stage->setDescription(<<<TEXT
            You have slain <.{{ creatureName }}.>. {% if textDefeated %}<<{{ textDefeated }}>{% endif %}
            
            You earn {{ gold }} gold.
            
            {% if bonusExperience < 0 %}
                Due to how easy this fight was, you earn {{ bonusExperience|abs }} less. In total, you earn {{ experience }} experience points!
            {% elseif bonusExperience > 0 %}
                Due to how difficult this fight was, you earn additional {{ bonusExperience }}. In total, you earn {{ experience }} experience points!
            {% else %}
                You earn {{ experience }} experience points!
            {% endif %}
            TEXT);
        } else {
            $stage->addContext("goldLost", $this->gold->getGold());
            $stage->addContext("experienceLost", round(0.1 * $this->stats->getExperience()));

            $stage->setDescription(<<<TEXT
            You have been slain by <.{{ creatureName }}.>. {% if textLost %}<<{{ textLost }}>{% endif %}
            
            You lost all your {{ goldLost}} gold, and {{ experienceLost }} experience points. Try better next time.
            TEXT);

            $this->logger->debug("Character {$stage->getOwner()->getId()} has been slain and lost {$this->gold->getGold()}.");
            $this->gold->setGold(0);
            $this->stats->setExperience((int)round(0.9 * $this->stats->getExperience()));
        }

        // For now, heal completely
        $this->health->heal(null);

        // Add standard navigation if battle is over
        $this->addSearchNavigation($stage, $scene);
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
        //  This is a 25% chance for 2, 3 and 4, and 12.5% for 1 and 5, making this effectively a 6.25% chance
        $level += $this->diceBag->chance(0.0625, 4) ? 1 : 0;

        // At the same time, a negative level increment is done if e_rand(1,3)==1
        // This is a 50% chance for 2, and a 25% chance for 1 and 3, making this effectively a 12.5% chance
        $level += $this->diceBag->chance(0.125, 4) ? 1 : 0;

        // There is are, effectively, 3 outcomes here:
        //  -  5.47% chance for a positive increase (P(plev) * (1-P(nlev))
        //  - 11.71% chance for a negative increase ((1-P(plev) * P(nlev))
        //  - 82.81% chance for no change (1 - P(plev) * (1-P(nlev) - (1-P(plev) * P(nlev))
        return $level;
    }
}