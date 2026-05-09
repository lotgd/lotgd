<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

class Buff
{
    const int ACTIVATES_ON_ROUNDSTART = 0b0001;
    const int ACTIVATES_ON_ROUNDEND = 0b0010;
    const int ACTIVATES_ON_OFFENSE_TURN = 0b0100;
    const int ACTIVATES_ON_DEFENSE_TURN = 0b1000;
    const int ACTIVATES_ON_BOTH_TURNS = 0b1100;
    const int ACTIVATES_NEVER = 0b0000;
    const int ACTIVATES_ANY = 0b1111;
    const int INFINITE_ROUNDS = -1;

    /**
     * @param string $id
     * @param string $name Name of the buff
     * @param int $activatesAt At which part of the battle the buff gets activated
     * @param int $rounds For how many rounds the buff stays. If == 0, the buff will expire. If < 0, the buff is permanent (unless expiresOnNewDay or expiresAfterBattle are turned on)
     * @param string|null $startMessage Message displayed if the buff is activates
     * @param string|null $roundMessage Message displayed if the buff is activate at the start of a round
     * @param string|null $endMessage Message displayed if the buff expires
     * @param string|null $effectSuccessMessage Message displayed if the buff's effect was successful
     * @param string|null $effectFailsMessage Message displayed if the buff's effect failed
     * @param string|null $noEffectMessage Message displayed if the buff has no effect
     * @param string|null $newDayMessage Message displayed on a new day if the buff is still there
     * @param bool $expiresOnNewDay True if the buff will be expired on a new day no matter how many rounds are left
     * @param bool $expiresAfterBattle True if the buff will be expired on the end of a battle no matter how many rounds are left
     * @param int $badGuyRegeneration Amount of health the badguy regenerates
     * @param int $goodGuyRegeneration Amount of health the goodguy regenerates
     * @param float $badGuyLifeTap Fraction of the damage caused to the badGuy that gets converted to health for the goodGuy
     * @param float $goodGuyLifeTap Fraction of the damage caused to the goodGuy that gets converted to health for the badGuy
     * @param float $badGuyDamageReflection Fraction of the damage caused to the badGuy that gets reflected to the goodGuy
     * @param float $goodGuyDamageReflection Fraction of the damage caused to the goodGuy that gets reflected to the badGuy
     * @param float $badGuyDamageModifier Effective damage done to the badGuy will be modified by this amount
     * @param float $goodGuyDamageModifier Effective damage done to the goodGuy will be modified by this amount
     * @param float $badGuyAttackModifier Modifies the badGuy's attack value before damage is calculated
     * @param float $goodGuyAttackModifier Modifies the goodGuy's attack value before damage is calculcated
     * @param float $badGuyDefenseModifier Modifies the badGuy's defense value before damage is calculcated
     * @param float $goodGuyDefenseModifier Modifies the goodGuy's defense value before damage is calculcated
     * @param bool $badGuyInvulnerable Set to true if the badGuy is completely invulnurable during the buff's duration
     * @param bool $goodGuyInvulnerable Set to true if the goodGuy is completely invulnurable during the buff's duration
     * @param int $numberOfMinions Number of minions that get summoned
     * @param int $minionMinBadGuyDamage Minimum damage that a minion causes to the badGuy
     * @param int $minionMaxBadGuyDamage Maximum damage that a minion causes to the badGuy. Uses pseudoBell to calculate the effective damage.
     * @param int $minionMinGoodGuyDamage Minimum damage that a minion causes to the goodGuy
     * @param int $minionMaxGoodGuyDamage Maximum damage that a minion causes to the goodGuy. Uses pseudoBell to calculate the effective damage.
     * @param bool $hasBeenStarted True if the buff has already been started
     * @param int $roundsUsed How many rounds this buff has already been used
     */
    public function __construct(
        protected(set) string $id {
            get {
                return $this->id;
            }
            set {
                $this->id = $value;
            }
        },
        protected(set) string $name {
            get {
                return $this->name;
            }
            set {
                $this->name = $value;
            }
        },
        protected(set) int $activatesAt {
            get {
                return $this->activatesAt;
            }
            set {
                $this->activatesAt = $value;
            }
        },
        protected(set) int $rounds {
            get {
                return $this->rounds;
            }
            set {
                $this->rounds = $value;
            }
        },
        protected(set) ?string $startMessage = null {
            get {
                return $this->startMessage;
            }
            set {
                $this->startMessage = $value;
            }
        },
        protected(set) ?string $roundMessage = null {
            get {
                return $this->roundMessage;
            }
            set {
                $this->roundMessage = $value;
            }
        },
        protected(set) ?string $endMessage = null {
            get {
                return $this->endMessage;
            }
            set {
                $this->endMessage = $value;
            }
        },
        protected(set) ?string $effectSuccessMessage = null {
            get {
                return $this->effectSuccessMessage;
            }
            set {
                $this->effectSuccessMessage = $value;
            }
        },
        protected(set) ?string $effectFailsMessage = null {
            get {
                return $this->effectFailsMessage;
            }
            set {
                $this->effectFailsMessage = $value;
            }
        },
        protected(set) ?string $noEffectMessage = null {
            get {
                return $this->noEffectMessage;
            }
            set {
                $this->noEffectMessage = $value;
            }
        },
        protected(set) ?string $newDayMessage = null {
            get {
                return $this->newDayMessage;
            }
            set {
                $this->newDayMessage = $value;
            }
        },
        protected(set) bool $expiresOnNewDay = true {
            get {
                return $this->expiresOnNewDay;
            }
            set {
                $this->expiresOnNewDay = $value;
            }
        },
        protected(set) bool $expiresAfterBattle = false {
            get {
                return $this->expiresAfterBattle;
            }
            set {
                $this->expiresAfterBattle = $value;
            }
        },
        protected(set) int $badGuyRegeneration = 0 {
            get {
                return $this->badGuyRegeneration;
            }
            set {
                $this->badGuyRegeneration = $value;
            }
        },
        protected(set) int $goodGuyRegeneration = 0 {
            get {
                return $this->goodGuyRegeneration;
            }
            set {
                $this->goodGuyRegeneration = $value;
            }
        },
        protected(set) float $badGuyLifeTap = 0. {
            get {
                return $this->badGuyLifeTap;
            }
            set {
                $this->badGuyLifeTap = $value;
            }
        },
        protected(set) float $goodGuyLifeTap = 0. {
            get {
                return $this->goodGuyLifeTap;
            }
            set {
                $this->goodGuyLifeTap = $value;
            }
        },
        protected(set) float $badGuyDamageReflection = 0. {
            get {
                return $this->badGuyDamageReflection;
            }
            set {
                $this->badGuyDamageReflection = $value;
            }
        },
        protected(set) float $goodGuyDamageReflection = 0. {
            get {
                return $this->goodGuyDamageReflection;
            }
            set {
                $this->goodGuyDamageReflection = $value;
            }
        },
        protected(set) float $badGuyDamageModifier = 1. {
            get {
                return $this->badGuyDamageModifier;
            }
            set {
                $this->badGuyDamageModifier = $value;
            }
        },
        protected(set) float $goodGuyDamageModifier = 1. {
            get {
                return $this->goodGuyDamageModifier;
            }
            set {
                $this->goodGuyDamageModifier = $value;
            }
        },
        protected(set) float $badGuyAttackModifier = 1. {
            get {
                return $this->badGuyAttackModifier;
            }
            set {
                $this->badGuyAttackModifier = $value;
            }
        },
        protected(set) float $goodGuyAttackModifier = 1. {
            get {
                return $this->goodGuyAttackModifier;
            }
            set {
                $this->goodGuyAttackModifier = $value;
            }
        },
        protected(set) float $badGuyDefenseModifier = 1. {
            get {
                return $this->badGuyDefenseModifier;
            }
            set {
                $this->badGuyDefenseModifier = $value;
            }
        },
        protected(set) float $goodGuyDefenseModifier = 1. {
            get {
                return $this->goodGuyDefenseModifier;
            }
            set {
                $this->goodGuyDefenseModifier = $value;
            }
        },
        protected(set) bool $badGuyInvulnerable = false {
            get {
                return $this->badGuyInvulnerable;
            }
            set {
                $this->badGuyInvulnerable = $value;
            }
        },
        protected(set) bool $goodGuyInvulnerable = false {
            get {
                return $this->goodGuyInvulnerable;
            }
            set {
                $this->goodGuyInvulnerable = $value;
            }
        },
        protected(set) int $numberOfMinions = 0 {
            get {
                return $this->numberOfMinions;
            }
            set {
                $this->numberOfMinions = $value;
            }
        },
        protected(set) int $minionMinBadGuyDamage = 0 {
            get {
                return $this->minionMinBadGuyDamage;
            }
            set {
                $this->minionMinBadGuyDamage = $value;
            }
        },
        protected(set) int $minionMaxBadGuyDamage = 0 {
            get {
                return $this->minionMaxBadGuyDamage;
            }
            set {
                $this->minionMaxBadGuyDamage = $value;
            }
        },
        protected(set) int $minionMinGoodGuyDamage = 0 {
            get {
                return $this->minionMinGoodGuyDamage;
            }
            set {
                $this->minionMinGoodGuyDamage = $value;
            }
        },
        protected(set) int $minionMaxGoodGuyDamage = 0 {
            get {
                return $this->minionMaxGoodGuyDamage;
            }
            set {
                $this->minionMaxGoodGuyDamage = $value;
            }
        },
        public bool $hasBeenStarted = false {
            get {
                return $this->hasBeenStarted;
            }
            set {
                $this->hasBeenStarted = $value;
            }
        },
        protected(set) int $roundsUsed = 0 {
            get {
                return $this->roundsUsed;
            }
            set {
                $this->roundsUsed = $value;
            }
        },
    ) {

    }

    /**
     * Consumes 1 round of the buff
     * @param int $rounds
     * @return void
     */
    public function consumeRound(int $rounds = 1): void
    {
        $this->roundsUsed+=1;
    }

    public function isExpired(): bool
    {
        // Always return false if the buff runs infinite amount of rounds
        if ($this->rounds < 0) {
            return false;
        }

        return $this->roundsUsed >= $this->rounds;
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function getsActivatedAt(int $flag): bool
    {
        if ($flag === self::ACTIVATES_NEVER) {
            return false;
        }

        return ($this->activatesAt & $flag) == true;
    }
}