<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\AbstractBattleEvent;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

readonly class BattleTurn
{
    public const int DamageTurnBoth = 0b11;
    public const int DamageTurnGoodGuy = 0b01;
    public const int DamageTurnBadGuy = 0b10;

    public function __construct(
        private DiceBagInterface $diceBag,
        private ?LoggerInterface $logger = null,
        private ?Stopwatch $stopwatch = null,
    ) {

    }

    /**
     * Processes a half-turn.
     * @param BattleState $battleState
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param BuffList $attackersBuffs
     * @param BuffList $defendersBuffs
     * @return ArrayCollection<int, AbstractBattleEvent>
     * @internal
     */
    public function partialTurn(
        BattleState $battleState,
        FighterInterface $attacker,
        FighterInterface $defender,
        BuffList $attackersBuffs,
        BuffList $defendersBuffs,
    ): ArrayCollection {
        $this->stopwatch?->start("lotgd2.BattleTurn.partialTurn");

        /** @var ArrayCollection<int, AbstractBattleEvent> $events */
        $events = new ArrayCollection();

        [$attackersAttack, $defendersDefense] = $this->calculateAttackAndDefense($battleState, $attacker, $defender, $attackersBuffs, $defendersBuffs);

        // Lets "roll the dice" to determine damage.
        $attackersAtkRoll = $this->diceBag->pseudoBell(0, $attackersAttack);
        $defendersDefRoll = $this->diceBag->pseudoBell(0, $defendersDefense);
        $damage = $attackersAtkRoll - $defendersDefRoll;

        $this->logger->debug("BattleTurn: Damage rolled: {$damage}");

        // If the attacker's attack after modification is bigger than before,
        // we call it a critical hit and apply the CriticalHitEvent.
        if ($battleState->isCriticalHitEnabled && $attackersAttack > $attacker->attack) {
            $events->add(new CriticalHitEvent($attacker, $defender, ["criticalAttackValue" => $attackersAttack]));
        }

        // Handle invincibility
        $attackerIsInvulnerable = $attackersBuffs->goodGuyInvulnerable || $defendersBuffs->badGuyInvulnerable;
        $defenderIsInvulnerable = $attackersBuffs->badGuyInvulnerable || $defendersBuffs->goodGuyInvulnerable;

        $this->logger->debug("BattleTurn: Invincibility check: Attacker ({$attackerIsInvulnerable}, Defender: {$defenderIsInvulnerable})");

        if ($attackerIsInvulnerable && $defenderIsInvulnerable) {
            // Both are invulnerable. We cannot set the damage to 0.
            $damage = 1;
        } elseif ($attackerIsInvulnerable) {
            // Attacker is invulnerable, damage is always > 0 (there is no riposte)
            $damage = abs($damage);
        } elseif ($defenderIsInvulnerable) {
            // Defender is invulnerable, damage is always < 0 (defender always ripostes)
            $damage = -abs($damage);
        }

        $this->logger->debug("BattleTurn: Damage after invincibility check: {$damage}");

        // Set damage to 0 if riposte has been disabled
        // This *will* increase the number of tries required to get a damaging round, as the probability of not doing
        //  damage increases.
        if ($damage < 0) {
            if ($battleState->isRiposteEnabled) {
                // If the damage is less than 0, it's a RIPOSTE. They are only half
                // as damaging as normal attacks.
                $damage /= 2;

                // Apply damage modification. It's a RIPOSTE, so the defender makes the damage. Therefore, we need to take
                //  the defender's goodGuyDamagerModifier into account and the attacker's badGuyDamageModifier
                $damage *= $defendersBuffs->goodGuyDamageModifier
                    * $attackersBuffs->badGuyDamageModifier;

                $this->logger->debug("BattleTurn: Damage modification upon RIPOSTE: {$damage} "
                    ."(def:gg:{$defendersBuffs->goodGuyDamageModifier}, atk:bg:{$attackersBuffs->badGuyDamageModifier})");
            } else {
                $damage = 0;
            }
        } else {
            // Apply damage modification. It is a normal attack, so the attacker makes the damage. Therefore, we need to take
            //  the attacker's goodGuyDamagerModifier into account and the defender's badGuyDamageModifier
            $damage *= $attackersBuffs->goodGuyDamageModifier
                * $defendersBuffs->badGuyDamageModifier;

            $this->logger->debug("BattleTurn: Damage modification: {$damage} "
                ."(atk:gg:{$attackersBuffs->goodGuyDamageModifier}, def:bg:{$defendersBuffs->badGuyDamageModifier})");
        }

        // Round the damage value and convert to int.
        $damage = (int)round($damage, 0);

        $this->logger->debug("BattleTurn: Final damage: {$damage}");

        // Add the damage event
        $events->add(new DamageEvent($attacker, $defender, ["damage" => $damage]));

        // Do all the other buff effects. Modifiers are calculated separately and do not need activation

        // Activates buffs to yield "activation" messages if necessary. Only activated buffs are processed by the BuffList
        $attackersBuffStartEvents = $attackersBuffs->activate(Buff::ACTIVATES_ON_OFFENSE_TURN, $attacker, $defender);
        $defendersBuffStartEvents = $defendersBuffs->activate(Buff::ACTIVATES_ON_DEFENSE_TURN, $attacker, $defender);

        // Process direct buffs. Direct buffs are buffs that summon minions that cause damage
        $attackersDirectBuffEvents = $attackersBuffs->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $attacker, $defender);
        $defendersDirectBuffEvents = $defendersBuffs->processDirectBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, $defender, $attacker);

        // Process damage-dependent buffs like life tap or damage reflection
        $attackersDamageDependentBuffEvents = $attackersBuffs->processDamageDependentBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $damage, $attacker, $defender);
        $defendersDamageDependentBuffEvents = $defendersBuffs->processDamageDependentBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, -$damage, $defender, $attacker);

        // The sequence of battle events does not depend on the status of the current round. We can freely resort the events now.
        // First, the attacker's buffs are announced and applied
        // Then, the defender's buffs are announced and applied
        // Then the normal battle-round begins
        // Damage-dependent buffs come last. Life-saving life-taps only help if the applicant doesn't die (yet).

        $events = new ArrayCollection([
            ...$attackersBuffStartEvents,
            ...$attackersDirectBuffEvents,
            ...$defendersBuffStartEvents,
            ...$defendersDirectBuffEvents,
            ...$events->toArray(),
            ...$attackersDamageDependentBuffEvents,
            ...$defendersDamageDependentBuffEvents,
        ]);

        $this->stopwatch?->stop("lotgd2.BattleTurn.partialTurn");
        return $events;
    }

    /**
     * Returns both offense and defense half-turns where for sure someone hits.
     *
     * Rounds where nobody hits are not interesting. To prevent this, the calculation of both half-turns is repeated
     * until at least one of the events is not 0.
     * @param BattleState $battleState
     * @param BuffList $goodGuyBuffList
     * @param BuffList $badGuyBuffList
     * @return array{ArrayCollection<int, covariant BattleEventInterface>, ArrayCollection<int, covariant BattleEventInterface>}
     * @internal
     */
    public function getHalfTurns(BattleState $battleState, BuffList $goodGuyBuffList, BuffList $badGuyBuffList): array
    {
        $i = 0;

        // Repeat as long as possible to prevent rounds where nobody hits.
        // This is how fights worked in 0.9.7+jt. Damages caused by buffs _do not count_.
        while (true) {
            $this->logger->debug("BattleTurn: Try #{$i}");

            // Evaluate both half-turns (offense and defense)
            $offenseTurn = $this->partialTurn($battleState, $battleState->goodGuy, $battleState->badGuy, $goodGuyBuffList, $badGuyBuffList);
            $defenseTurn = $this->partialTurn($battleState, $battleState->badGuy, $battleState->goodGuy, $badGuyBuffList, $goodGuyBuffList);

            $this->logger->debug("BattleTurn: Result", [
                "offense" => $offenseTurn,
                "defense" => $defenseTurn,
            ]);

            // Return any raised DamageEvent.
            $offenseDamageEvent = $offenseTurn->findFirst(fn(int $k, BattleEventInterface $e) => $e instanceof DamageEvent);
            $defenseDamageEvent = $defenseTurn->findFirst(fn(int $k, BattleEventInterface $e) => $e instanceof DamageEvent);

            // If this was the 21st time, let's call it a day. The probability of this result changing is at this point trivial
            if ($i >= 20) {
                $this->logger->critical("There is something wrong with a battle; it should not need 20 evaluations.", context: [
                    "battleState" => $battleState,
                    "goodGuy" => $battleState->goodGuy,
                    "badGuy" => $battleState->badGuy,
                    "goodGuyBuffList" => $goodGuyBuffList,
                    "badGuyBuffList" => $badGuyBuffList,
                ]);

                break;
            }

            // If at least one of the two damage events is not zero, we stop the cycle to return the events.
            if ((
                    $offenseDamageEvent instanceof DamageEvent and $offenseDamageEvent->getDamage() <> 0
                ) || (
                    $defenseDamageEvent instanceof DamageEvent and $defenseDamageEvent->getDamage() <> 0
                )) {
                break;
            }

            $i++;
        }

        return [$offenseTurn, $defenseTurn];
    }

    /**
     * Makes different adjustments to the attacker's attack and the defender's defense and returns the modified values
     * Adjustments:
     *  - Level adjustment (defenders with lower levels have decreased defense; such with higher levels has increased defense)
     *  - Critical hit adjustments
     *  - Buffs (later)
     *
     * @param BattleState $battleState
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param BuffList $attackersBuffs
     * @param BuffList $defendersBuffs
     * @return int[]
     */
    public function calculateAttackAndDefense(
        BattleState $battleState,
        FighterInterface $attacker,
        FighterInterface $defender,
        BuffList $attackersBuffs,
        BuffList $defendersBuffs,
    ): array {
        $adjustment = 1.0;
        $defenseAdjustment = 1.0;

        // Adjustment makes fights versus monsters with lower level easier;
        // and more difficult if the monster has a higher level by adjusting
        // the monster's defense value.
        // For example, if a level 10 player attacks a level 9 monster, the
        // defenseAdjustment value for the monster is 0.81, reducing the monster's
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

        // Apply buff scaling for the attacker's attack. This needs to take into account the attacker's goodGuyAttackModifier,
        //  and the defender's badGuyAttackModifier.
        $attackersAttack = $attacker->attack
            * $attackersBuffs->goodGuyAttackModifier
            * $defendersBuffs->badGuyAttackModifier
        ;

        $this->logger->debug("BattleTurn: Attackers Attack ($attacker->name}: $attackersAttack "
            ."({$attacker->attack} * {$attackersBuffs->goodGuyAttackModifier} * {$defendersBuffs->badGuyAttackModifier})");

        // Apply buff scaling for the defender's defense. This needs to take into account the defender's goodGuyDefenseModifier,
        //  and the attacker's badGuyDefenseModifier
        $defendersDefense = $defender->defense * $defenseAdjustment
            * $defendersBuffs->goodGuyDefenseModifier
            * $attackersBuffs->badGuyDefenseModifier
        ;

        $this->logger->debug("BattleTurn: Defenders Defense ($defender->name}: $defendersDefense "
            ."({$defender->defense} * {$defenseAdjustment} * {$defendersBuffs->goodGuyDefenseModifier} * {$attackersBuffs->badGuyDefenseModifier})");

        // If the player is the attacker, we enable critical hits with a chance of 2.63%.
        if ($battleState->isCriticalHitEnabled && $attacker instanceof CurrentCharacterFighter) {
            // Players can land critical hits
            // The original code asks for e_rand(1,20)==1
            // This equals to a chance of (1/19)/2=0.0263 (20 possible states, but two are half as likely)
            if ($this->diceBag->chance(0.0263, precision: 4)) {
                $this->logger->debug("BattleTurn: Enable critical hit.");
                $attackersAttack *= 3;
            }
        }

        // Conversion from float to int, since the random number generator takes int values.
        $attackersAttack = (int)round($attackersAttack, 0);
        $defendersDefense = (int)round($defendersDefense, 0);

        $this->logger->debug("Definite Attack ({$attacker->name}): $attackersAttack, defense: $defendersDefense");

        return [$attackersAttack, $defendersDefense];
    }
}