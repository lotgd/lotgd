<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A battle event that reports on damage from minions.
 *
 * If damage is positive, effectSucceeds will be used as the battle message.
 * If damage is negative, effectFails will be used as the battle message despite the effect technically not failing.
 *   This behaviour is not considered a bug and is congruent with 0.9.7+jt.
 * If the damage is exactly 0, noEffect will be used as the battle message.
 *
 * @phpstan-type MinionDamageContext array{
 *      target: "attacker"|"defender",
 *      damage: int,
 *      effectSucceeds?: ?string,
 *      effectFails?: ?string,
 *      noEffect?: ?string,
 *  }
 * @extends AbstractBattleEvent<MinionDamageContext>
 */
class MinionDamageEvent extends AbstractBattleEvent
{
    readonly protected(set) FighterInterface $target;

    protected(set) ?string $message = null {
        get => $this->message;
        set(null|string $value) => $value;
    }

    readonly protected(set) int $damage;

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param MinionDamageContext $context
     */
    public function __construct(
        private readonly FighterInterface $attacker,
        private readonly FighterInterface $defender,
        array $context
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("damage")->allowedTypes("int")->required();
        $resolver->define("target")->allowedTypes("string")->allowedValues("attacker", "defender");
        $resolver->define("effectSucceeds")->allowedTypes("string", "null")->default(null);
        $resolver->define("effectFails")->allowedTypes("string", "null")->default(null);
        $resolver->define("noEffect")->allowedTypes("string", "null")->default(null);
        $this->context = $resolver->resolve($context);

        $this->target = $this->context["target"] === "attacker" ? $this->attacker : $this->defender;
        $this->damage = $this->context["damage"];
    }

    public function apply(): void
    {
        parent::apply();

        // If damage is negative, the victim will be _healed_. This is the same behaviour as seen in 0.9.7+jt ext GER 3.
        // Can be used to have minions _heal_ the character.
        $victim = $this->context["target"] === "attacker" ? $this->attacker : $this->defender;

        if ($this->damage < 0) {
            $this->message = $this->context["effectFails"];
        } elseif ($this->damage > 0) {
            $this->message = $this->context["effectSucceeds"];
        } else {
            $this->message = $this->context["noEffect"];
        }

        $victim->damage($this->damage);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        if ($this->message === null) {
            return null;
        }

        return new BattleMessage($this->message, [
            "attacker" => $this->attacker,
            "defender" => $this->defender,
            "damage" => $this->damage,
            "target" => $this->context["target"],
            "buffTarget" => $this->target,
        ]);
    }
}