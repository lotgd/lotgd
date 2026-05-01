<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

/**
 * ProtoBuff is a variant of Buff that allows strings for most properties.
 *
 * To convert into a buff, it requires proper evaluation via the ExpressionService.
 */
class ProtoBuff
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
     * @param string|int $badGuyRegeneration Amount of health the badguy regenerates
     * @param string|int $goodGuyRegeneration Amount of health the goodguy regenerates
     * @param string|float $badGuyLifeTap Fraction of the damage caused to the badGuy that gets converted to health for the goodGuy
     * @param string|float $goodGuyLifeTap Fraction of the damage caused to the goodGuy that gets converted to health for the badGuy
     * @param string|float $badGuyDamageReflection Fraction of the damage caused to the badGuy that gets reflected to the goodGuy
     * @param string|float $goodGuyDamageReflection Fraction of the damage caused to the goodGuy that gets reflected to the badGuy
     * @param string|float $badGuyDamageModifier Effective damage done to the badGuy will be modified by this amount
     * @param string|float $goodGuyDamageModifier Effective damage done to the goodGuy will be modified by this amount
     * @param string|float $badGuyAttackModifier Modifies the badGuy's attack value before damage is calculated
     * @param string|float $goodGuyAttackModifier Modifies the goodGuy's attack value before damage is calculcated
     * @param string|float $badGuyDefenseModifier Modifies the badGuy's defense value before damage is calculcated
     * @param string|float $goodGuyDefenseModifier Modifies the goodGuy's defense value before damage is calculcated
     * @param string|bool $badGuyInvulnerable Set to true if the badGuy is completely invulnurable during the buff's duration
     * @param string|bool $goodGuyInvulnerable Set to true if the goodGuy is completely invulnurable during the buff's duration
     * @param string|int $numberOfMinions Number of minions that get summoned
     * @param string|int $minionMinBadGuyDamage Minimum damage that a minion causes to the badGuy
     * @param string|int $minionMaxBadGuyDamage Maximum damage that a minion causes to the badGuy. Uses pseudoBell to calculate the effective damage.
     * @param string|int $minionMinGoodGuyDamage Minimum damage that a minion causes to the goodGuy
     * @param string|int $minionMaxGoodGuyDamage Maximum damage that a minion causes to the goodGuy. Uses pseudoBell to calculate the effective damage.
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
        protected(set) string|int $badGuyRegeneration = 0,
        protected(set) string|int $goodGuyRegeneration = 0,
        protected(set) string|float $badGuyLifeTap = 0.,
        protected(set) string|float $goodGuyLifeTap = 0.,
        protected(set) string|float $badGuyDamageReflection = 0.,
        protected(set) string|float $goodGuyDamageReflection = 0.,
        protected(set) string|float $badGuyDamageModifier = 1.,
        protected(set) string|float $goodGuyDamageModifier = 1.,
        protected(set) string|float $badGuyAttackModifier = 1.,
        protected(set) string|float $goodGuyAttackModifier = 1.,
        protected(set) string|float $badGuyDefenseModifier = 1.,
        protected(set) string|float $goodGuyDefenseModifier = 1.,
        protected(set) string|bool $badGuyInvulnerable = false,
        protected(set) string|bool $goodGuyInvulnerable = false,
        protected(set) string|int $numberOfMinions = 0,
        protected(set) string|int $minionMinBadGuyDamage = 0,
        protected(set) string|int $minionMaxBadGuyDamage = 0,
        protected(set) string|int $minionMinGoodGuyDamage = 0,
        protected(set) string|int $minionMaxGoodGuyDamage = 0,
    ) {

    }
}