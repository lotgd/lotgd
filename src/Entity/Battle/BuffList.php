<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use LotGD2\Game\Battle\BattleEvent\AbstractBattleEvent;
use LotGD2\Game\Battle\BattleEvent\BuffMessageEvent;
use LotGD2\Game\Battle\BattleEvent\DamageReflectionEvent;
use LotGD2\Game\Battle\BattleEvent\LifeTapEvent;
use LotGD2\Game\Battle\BattleEvent\MinionDamageEvent;
use LotGD2\Game\Battle\BattleEvent\RegenerationBuffEvent;
use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;
use ValueError;
use function round;

class BuffList
{
    /**
     * @var array{
     *     1?: Buff[],
     *     2?: Buff[],
     *     4?: Buff[],
     *     8?: Buff[],
     * }
     */
    protected(set) array $activeBuffs = [] {
        get => $this->activeBuffs;
        set(array $value) => $value;
    }

    /** @var Buff[] */
    protected(set) array $usedBuffs = [] {
        get => $this->usedBuffs;
        set(array $value) => $value;
    }


    public float $badGuyDamageModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->badGuyDamageModifier, $this->buffs));
    }

    public float $goodGuyDamageModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->goodGuyDamageModifier, $this->buffs));
    }

    public float $badGuyAttackModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->badGuyAttackModifier, $this->buffs));
    }

    public float $goodGuyAttackModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->goodGuyAttackModifier, $this->buffs));
    }

    public float $badGuyDefenseModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->badGuyDefenseModifier, $this->buffs));
    }

    public float $goodGuyDefenseModifier {
        get => array_product(array_map(fn (Buff $buff) => $buff->goodGuyDefenseModifier, $this->buffs));
    }

    public bool $badGuyInvulnerable {
        get => array_any($this->buffs, fn(Buff $buff) => $buff->badGuyInvulnerable);
    }

    public bool $goodGuyInvulnerable {
        get => array_any($this->buffs, fn(Buff $buff) => $buff->goodGuyInvulnerable);
    }

    /**
     * @param Buff[] $buffs
     */
    public function __construct(
        private LoggerInterface $logger,
        private DiceBagInterface $diceBag,
        protected(set) array $buffs = [] {
            get => $this->buffs;
            set => $value;
        },
    ) {
    }

    /**
     * @param int $activation
     * @param FighterInterface $goodGuy
     * @param FighterInterface $badGuy
     * @return AbstractBattleEvent[]
     */
    public function activate(int $activation, FighterInterface $goodGuy, FighterInterface $badGuy): array
    {
        if ((($activation & ($activation - 1)) != 0) === true) {
            throw new ValueError("Only one type of buff activation is permitted to activate at a time");
        }

        if (!in_array($activation, [Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_ON_ROUNDEND, Buff::ACTIVATES_ON_OFFENSE_TURN, Buff::ACTIVATES_ON_DEFENSE_TURN])) {
            throw new ValueError("Activation is not on the list of possible activation types ($activation given)");
        }

        if (isset($this->activeBuffs[$activation]) && count($this->activeBuffs[$activation]) > 0) {
            throw new ValueError("You already have activated the buffs for activation type ($activation given).");
        }

        $this->logger->debug("BuffList: Activate on {$activation} for gg:{$goodGuy->name} and bg:{$badGuy->name}");

        $events = [];

        $currentlyActivatingBuffs = [];

        foreach ($this->buffs as $buff) {
            if ($buff->getsActivatedAt($activation) === false) {
                continue;
            }

            $this->logger->debug("BuffList: Activate: {$buff->name}");

            $currentlyActivatingBuffs[] = $buff;

            $buffMessage = $this->getBuffMessage($buff);
            if ($buffMessage !== null) {
                $events[] = new BuffMessageEvent($goodGuy, $badGuy, ["message" => $buffMessage]);
            }

            if ($this->hasBuffBeenUsed($buff) === false) {
                $this->logger->debug("BuffList: Mark as used: {$buff->name}");
                $this->usedBuffs = [... $this->usedBuffs, $buff];
            }
        }

        $this->activeBuffs = [... $this->activeBuffs, $activation => $currentlyActivatingBuffs];

        return $events;
    }

    public function getBuffMessage(Buff $buff): ?string
    {
        if ($this->hasBuffBeenUsed($buff)) {
            return null;
        } elseif ($buff->hasBeenStarted === false) {
            $return = $buff->startMessage;
            $buff->hasBeenStarted = true;
        } else {
            $return = $buff->roundMessage;
        }

        return $return;
    }

    public function hasBuffBeenUsed(Buff $buff): bool
    {
        return in_array($buff, $this->usedBuffs, true);
    }

    /**
     * @param int $activation
     * @param FighterInterface $goodGuy
     * @param FighterInterface $badGuy
     * @return AbstractBattleEvent[]
     */
    public function processDirectBuffs(
        int $activation,
        FighterInterface $goodGuy,
        FighterInterface $badGuy
    ): array {
        $events = [];

        foreach ($this->activeBuffs[$activation] as $buff) {
            // Add good guy regeneration
            if ($buff->goodGuyRegeneration !== 0) {
                $events[] = new RegenerationBuffEvent(
                    $goodGuy,
                    $badGuy,
                    [
                        "target" => "attacker",
                        "healAmount" => $buff->goodGuyRegeneration,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                    ],
                );
            }

            // Add bad guy regeneration
            if ($buff->badGuyRegeneration !== 0) {
                $events[] = new RegenerationBuffEvent(
                    $goodGuy,
                    $badGuy,
                    [
                        "target" => "defender",
                        "healAmount" => $buff->badGuyRegeneration,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                    ],
                );
            }

            // Minion buff
            if ($buff->numberOfMinions > 0) {
                $n = $buff->numberOfMinions;
                $attacksOne = ($buff->minionMinGoodGuyDamage !== 0 || $buff->minionMaxGoodGuyDamage !== 0)
                    || ($buff->minionMinBadGuyDamage !== 0 || $buff->minionMaxBadGuyDamage !== 0);

                $attacksBoth = ($buff->minionMinGoodGuyDamage !== 0 || $buff->minionMaxGoodGuyDamage !== 0)
                    && ($buff->minionMinBadGuyDamage !== 0 || $buff->minionMaxBadGuyDamage !== 0);

                // Faulty buff - if minions attack no one, it's better to have no minions at all. Or they will just do... nothing.
                if ($attacksOne === false) {
                    $n = 0;
                }

                // Add a minion event for every single minion
                for ($i = 0; $i < $n; $i++) {
                    // If the buff is set up to attack both good and badguy, we throw a dice to decide who the minion attacks
                    if ($attacksBoth === true) {
                        if ($this->diceBag->chance(0.5)) {
                            $who = 1;
                        } else {
                            $who = -1;
                        }
                    } elseif ($buff->minionMinGoodGuyDamage !== 0 || $buff->minionMaxGoodGuyDamage !== 0) {
                        $who = 1;
                    } else {
                        $who = -1;
                    }

                    if ($who === 1) {
                        // Minion does damage to the goodguy
                        $damage = $this->diceBag->pseudoBell($buff->minionMinGoodGuyDamage, $buff->minionMaxGoodGuyDamage);
                        $target = "attacker";
                    } else {
                        // Minion does damage to the badguy
                        $damage = $this->diceBag->pseudoBell($buff->minionMinBadGuyDamage, $buff->minionMaxBadGuyDamage);
                        $target = "defender";
                    }

                    $events[] = new MinionDamageEvent(
                        $goodGuy,
                        $badGuy,
                        [
                            "target" => $target,
                            "damage" => (int)round($damage, 0),
                            "effectSucceeds" => $buff->effectSuccessMessage,
                            "effectFails" => $buff->effectFailsMessage,
                            "noEffect" => $buff->noEffectMessage,
                        ],
                    );
                }
            }
        }

        return $events;
    }

    /**
     * @param int $activation
     * @param int $damage
     * @param FighterInterface $goodGuy
     * @param FighterInterface $badGuy
     * @return AbstractBattleEvent[]
     */
    public function processDamageDependentBuffs(
        int $activation,
        int $damage,
        FighterInterface $goodGuy,
        FighterInterface $badGuy
    ): array {
        $events = [];

        foreach ($this->activeBuffs[$activation] ?? [] as $buff) {
            if ($buff->goodGuyDamageReflection > 0.) {
                $events[] = new DamageReflectionEvent(
                    attacker: $goodGuy,
                    defender: $badGuy,
                    context: [
                        "damageTarget" => "attacker",
                        "reflectionTarget" => "defender",
                        "damage" => $damage,
                        "reflection" => $buff->goodGuyDamageReflection,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                        "noEffect" => $buff->noEffectMessage,
                    ],
                );
            }

            if ($buff->badGuyDamageReflection > 0.) {
                $events[] = new DamageReflectionEvent(
                    attacker: $goodGuy,
                    defender: $badGuy,
                    context: [
                        "damageTarget" => "defender",
                        "reflectionTarget" => "attacker",
                        "damage" => $damage,
                        "reflection" => $buff->badGuyDamageReflection,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                        "noEffect" => $buff->noEffectMessage,
                    ],
                );
            }

            if ($buff->goodGuyLifeTap > 0.) {
                $events[] = new LifeTapEvent(
                    attacker: $goodGuy,
                    defender: $badGuy,
                    context: [
                        "damageVictim" => "attacker",
                        "healTarget" => "defender",
                        "damage" => $damage,
                        "lifeTap" => $buff->goodGuyLifeTap,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                        "noEffect" => $buff->noEffectMessage,
                    ],
                );
            }

            if ($buff->badGuyLifeTap > 0.) {
                $events[] = new LifeTapEvent(
                    attacker: $goodGuy,
                    defender: $badGuy,
                    context: [
                        "damageVictim" => "defender",
                        "healTarget" => "attacker",
                        "damage" => $damage,
                        "lifeTap" => $buff->badGuyLifeTap,
                        "effectSucceeds" => $buff->effectSuccessMessage,
                        "effectFails" => $buff->effectFailsMessage,
                        "noEffect" => $buff->noEffectMessage,
                    ],
                );
            }
        }

        return $events;
    }

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @return AbstractBattleEvent[]
     */
    public function expireOneRound(FighterInterface $attacker, FighterInterface $defender): array
    {
        $events = [];

        foreach ($this->usedBuffs as $buff) {
            $buff->consumeRound();

            if ($buff->isExpired()) {
                // If the buff is expired, we need to remove it from the active buffs.
                $this->remove($buff);

                $endMessage = $buff->endMessage;

                if ($endMessage !== null) {
                    $events[] = new BuffMessageEvent(
                        $attacker,
                        $defender,
                        ["message" => $endMessage],
                    );
                }
            }
        }

        return $events;
    }

    /**
     * Removes a given buff from its list.
     *
     * If the buff is not in the list, remove will silently fail.
     * @param Buff $buff
     * @return void
     */
    public function remove(Buff $buff): void
    {

        $buffs = $this->buffs;
        $offset = array_search($buff, $buffs, true);
        if ($offset !== false) {
            $this->logger->debug("Removing buff: {$buff->name}.");
            array_splice($buffs, $offset, 1);

            $this->buffs = $buffs;
        } else {
            $this->logger->debug("Removing buff: {$buff->name}, but was not contained.");
        }
    }
}