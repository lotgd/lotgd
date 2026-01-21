<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BasicFighterInterface;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function round;

/**
 * @phpstan-import-type BattleEventCollection from BattleEventInterface
 * @phpstan-import-type DamageEventCollection from DamageEvent
 */
class Battle
{
    const string ActionGroupBattle = "lotgd2.actionGroup.battle";
    const string FightActionAttack = "lotgd2.action.attack";
    const string FightActionFlee = "lotgd2.action.flee";

    public function __construct(
        private LoggerInterface $logger,
        private readonly DenormalizerInterface&NormalizerInterface $normalizer,
        private readonly BattleTurn $turn,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function start(
        BasicFighterInterface $badGuy,
        bool $isLevelAdjustmentEnabled = true,
        bool $isCriticalHitEnabled = true,
        bool $isRiposteEnabled = true,
        bool $allowFlee = true,
    ): BattleState {
        $this->logger->debug("Starting Battle");

        $badGuy = new Fighter(... $this->normalizer->normalize($badGuy, context: ["groups" => ["fighter"]]));

        $battleState = new BattleState(
            CurrentCharacterFighter::fromCharacter($this->character),
            $badGuy,
            $isLevelAdjustmentEnabled,
            $isCriticalHitEnabled,
            $isRiposteEnabled,
            $allowFlee,
        );
        $battleState->setCharacter($this->character);

        return $battleState;
    }

    /**
     * Adds actions for fighting
     * @param Stage $stage
     * @param Scene $scene
     * @param BattleState $battleState
     * @param array<string, mixed> $actionParams
     * @return void
     */
    public function addFightActions(Stage $stage, Scene $scene, BattleState $battleState, array $actionParams = []): void
    {
        $this->logger->debug("Adding Battle Actions");
        $actionGroup = new ActionGroup(self::ActionGroupBattle, "Fight", -100);

        $actionGroup->setActions([
            new Action($scene, "Attack", [... $actionParams, "how" => "attack", "battleState" => $battleState], reference: self::FightActionAttack),
        ]);

        if ($battleState->allowFlee) {
            $actionGroup->addAction(new Action($scene, "Flee", [... $actionParams, "how" => "flee", "battleState" => $battleState], reference: self::FightActionFlee));
        }

        $stage->addActionGroup($actionGroup);
    }

    /**
     * Fights exactly one round (1 offense and 1 defense turn)
     * @param BattleState $battleState
     * @param int $damageRound
     * @return void
     */
    public function fightOneRound(BattleState $battleState, int $damageRound): void
    {
        $battleState->setCharacter($this->character);

        [$offenseTurn, $defenseTurn] = $this->turn->getHalfTurns($battleState);

        // Only add offense turns if its part of the current round
        $offenseTurnEvents = $damageRound & BattleTurn::DamageTurnGoodGuy ? $offenseTurn : [];

        // Only add defense turns if its part of the current round
        $defenseTurnEvents = $damageRound & BattleTurn::DamageTurnBadGuy ? $defenseTurn : [];

        // Process events
        $events = new ArrayCollection([... $offenseTurnEvents, ... $defenseTurnEvents]);
        $eventsToAdd = $this->processBattleEvents($events, $battleState);

        // Post round clean-up
        $battleState->incrementRound();
        $battleState->addMessages($eventsToAdd->map(fn (BattleEventInterface $event) => $event->decorate()));
        $battleState->syncronizeToCharacter();
    }

    /**
     * @param BattleEventCollection $events
     * @param BattleState $battleState
     * @return BattleEventCollection
     */
    public function processBattleEvents(ArrayCollection $events, BattleState $battleState): ArrayCollection
    {
        /** @var BattleEventCollection $eventsToAdd */
        $eventsToAdd = new ArrayCollection();

        foreach ($events as $event) {
            $event->apply();

            $eventsToAdd->add($event);

            // DEATH
            if ($battleState->goodGuy->health <= 0) {
                $eventsToAdd->add(new DeathEvent($battleState->goodGuy, $battleState->badGuy, ["victim" => $battleState->goodGuy]));
                $battleState->result = BattleStateStatusEnum::BadGuyWon;
                break;
            }

            if ($battleState->badGuy->health <= 0) {
                $deathEvent = new DeathEvent($battleState->goodGuy, $battleState->badGuy, ["victim" => $battleState->badGuy]);
                $eventsToAdd->add($deathEvent);
                $battleState->result = BattleStateStatusEnum::GoodGuyWon;
                break;
            }
        }

        return $eventsToAdd;
    }
}