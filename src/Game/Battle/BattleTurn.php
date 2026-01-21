<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\AbstractBattleEvent;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Random\DiceBagInterface;

readonly class BattleTurn
{
    public const int DamageTurnBoth = 0b11;
    public const int DamageTurnGoodGuy = 0b01;
    public const int DamageTurnBadGuy = 0b10;

    public function __construct(
        private DiceBagInterface $diceBag,
    ) {

    }

    /**
     * Processes a half-turn.
     * @internal
     * @param BattleState $battleState
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @return ArrayCollection<int, AbstractBattleEvent>
     */
    public function partialTurn(BattleState $battleState, FighterInterface $attacker, FighterInterface $defender): ArrayCollection
    {
        $events = new ArrayCollection();

        [$attackersAttack, $defendersDefense] = $this->calculateAttackAndDefense($battleState, $attacker, $defender);

        // Lets "roll the dice".
        $attackersAtkRoll = $this->diceBag->pseudoBell(0, $attackersAttack);
        $defendersDefRoll = $this->diceBag->pseudoBell(0, $defendersDefense);
        $damage = $attackersAtkRoll - $defendersDefRoll;

        // If the attacker's attack after modification is bigger than before,
        // we call it a critical hit and apply the CriticalHitEvent.
        if ($battleState->isCriticalHitEnabled && $attackersAttack > $attacker->attack) {
            $events->add(new CriticalHitEvent($attacker, $defender, ["criticalAttackValue" => $attackersAttack]));
        }

        // Set damage to 0 if riposte has been disabled
        // This *will* increase the number of tries required to get a damaging round, as the probability of not doing
        //  damage increases.
        if ($damage < 0) {
            if ($battleState->isRiposteEnabled) {
                // If the damage is less then 0, it's a RIPOSTE. They are only half
                // as damaging than normal attacks.
                $damage /= 2;
            } else {
                $damage = 0;
            }
        }

        // Round the damage value and convert to int.
        $damage = (int)round($damage, 0);

        // Add the damage event
        $events->add(new DamageEvent($attacker, $defender, ["damage" => $damage]));

        return $events;
    }

    /**
     * Returns both offense and defense half-turns where for sure someone hits.
     *
     * Rounds where nobody hits are not interesting. To prevent this, the calculation of both half-turns is repeated
     * until at least one of the events is not 0.
     * @param BattleState $battleState
     * @return array{ArrayCollection<int, covariant BattleEventInterface>, ArrayCollection<int, covariant BattleEventInterface>}
     * @internal
     */
    public function getHalfTurns(BattleState $battleState): array
    {
        // Repeat as long as possible to prevent rounds where nobody hits.
        while (true) {
            $offenseTurn = $this->partialTurn($battleState, $battleState->goodGuy, $battleState->badGuy);
            $defenseTurn = $this->partialTurn($battleState, $battleState->badGuy, $battleState->goodGuy);

            $offenseDamageEvent = $offenseTurn->findFirst(fn(int $k, BattleEventInterface $e) => $e instanceof DamageEvent);
            $defenseDamageEvent = $defenseTurn->findFirst(fn(int $k, BattleEventInterface $e) => $e instanceof DamageEvent);

            if ((
                    $offenseDamageEvent instanceof DamageEvent and $offenseDamageEvent->getDamage() <> 0
                ) || (
                    $defenseDamageEvent instanceof DamageEvent and $defenseDamageEvent->getDamage() <> 0
                )) {
                break;
            }
        }

        return [$offenseTurn, $defenseTurn];
    }

    /**
     * Makes different adjustments to the attacker's attack and the defenders defense and returns the modified values
     * Adjustments:
     *  - Level adjustment (defenders with lower levels have decreased defense; such with higher levels have increased defense)
     *  - Critical hit adjustments
     *  - Buffs (later)
     *
     * @param BattleState $battleState
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @return int[]
     */
    public function calculateAttackAndDefense(BattleState $battleState, FighterInterface $attacker, FighterInterface $defender): array
    {
        $adjustment = 1.0;
        $defenseAdjustment = 1.0;

        // Adjustement makes fights versus monsters with lower level easier;
        // and more difficult if the monster has a higher level by adjusting
        // the monster's defense value.
        // For example, if a level 10 player attacks a level 9 monster, the
        // defenseAdjustement value for the monster is 0.81, reducing the monster's
        // defense by 20% and making it more likely for the player to land a hit.
        // On the other hand, the player's defense is increased by ~ 10%, making it
        // less likely for the enemy to hit the player.
        if ($battleState->isLevelAdjustmentEnabled) {
            if ($attacker instanceof CurrentCharacterFighter) {
                if ($attacker->level > 1 && $defender->level > 1) {
                    $adjustment = $attacker->level / $defender->level;
                    $defenseAdjustment = 1. / ($adjustment * $adjustment);
                }
            } elseif ($defender instanceof CurrentCharacterFighter) {
                if ($attacker->level > 1 && $defender->level > 1) {
                    $adjustment = $defender->level / $attacker->level;
                    $defenseAdjustment = $adjustment;
                }
            }
        }

        $attackersAttack = $attacker->attack;
        $defendersDefense = $defender->defense * $defenseAdjustment;

        // If the player is the attacker, we enable critical hits with a chance of 2.63%.
        if ($battleState->isCriticalHitEnabled && $attacker instanceof CurrentCharacterFighter) {
            // Players can land critical hits
            // The original code asks for e_rand(1,20)==1
            // This equals to a chance of (1/19)/2=0.0263 (20 possible states, but two are half as likely)
            if ($this->diceBag->chance(0.0263, precision: 4)) {
                $attackersAttack *= 3;
            }
        }

        // Conversion from float to int, since the random number generator takes int values.
        $attackersAttack = (int)round($attackersAttack, 0);
        $defendersDefense = (int)round($defendersDefense, 0);

        return [$attackersAttack, $defendersDefense];
    }
}