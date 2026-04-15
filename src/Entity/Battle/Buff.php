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
        protected(set) string $id,
        protected(set) string $name,
        protected(set) int $activatesAt,
        protected(set) int $rounds,
        protected(set) ?string $startMessage = null,
        protected(set) ?string $roundMessage = null,
        protected(set) ?string $endMessage = null,
        protected(set) ?string $effectSuccessMessage = null,
        protected(set) ?string $effectFailsMessage = null,
        protected(set) ?string $noEffectMessage = null,
        protected(set) ?string $newDayMessage = null,
        protected(set) bool $expiresOnNewDay = true,
        protected(set) bool $expiresAfterBattle = false,
        protected(set) int $badGuyRegeneration = 0,
        protected(set) int $goodGuyRegeneration = 0,
        protected(set) float $badGuyLifeTap = 0.,
        protected(set) float $goodGuyLifeTap = 0.,
        protected(set) float $badGuyDamageReflection = 0.,
        protected(set) float $goodGuyDamageReflection = 0.,
        protected(set) float $badGuyDamageModifier = 0.,
        protected(set) float $goodGuyDamageModifier = 0.,
        protected(set) float $badGuyAttackModifier = 0.,
        protected(set) float $goodGuyAttackModifier = 0.,
        protected(set) float $badGuyDefenseModifier = 0.,
        protected(set) float $goodGuyDefenseModifier = 0.,
        protected(set) bool $badGuyInvulnerable = false,
        protected(set) bool $goodGuyInvulnerable = false,
        protected(set) int $numberOfMinions = 0,
        protected(set) int $minionMinBadGuyDamage = 0,
        protected(set) int $minionMaxBadGuyDamage = 0,
        protected(set) int $minionMinGoodGuyDamage = 0,
        protected(set) int $minionMaxGoodGuyDamage = 0,
        public bool $hasBeenStarted = false,
        protected(set) int $roundsUsed = 0,
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