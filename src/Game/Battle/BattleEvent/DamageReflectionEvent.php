<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type DamageReflectionContext array{
 *       damageTarget: "attacker"|"defender",
 *       reflectionTarget: "attacker"|"defender",
 *       damage: int,
 *       reflection: float,
 *       effectSucceeds?: ?string,
 *       effectFails?: ?string,
 *       noEffect?: ?string,
 *  }
 * @extends AbstractBattleEvent<DamageReflectionContext>
 */
class DamageReflectionEvent extends AbstractBattleEvent
{
    private(set) ?string $message = null;
    private(set) int $reflectedDamage = 0;
    private(set) FighterInterface $damageTarget {
        get => $this->damageTarget;
        set(FighterInterface $value) {
            $this->damageTarget = $value;
        }
    }

    private(set) FighterInterface $reflectionTarget {
        get => $this->reflectionTarget;
        set(FighterInterface $value) {
            $this->reflectionTarget = $value;
        }
    }

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param DamageReflectionContext $context
     */
    public function __construct(
        private FighterInterface $attacker,
        private FighterInterface $defender,
        array $context,
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("damageTarget")->allowedTypes("string")->allowedValues("attacker", "defender");
        $resolver->define("reflectionTarget")->allowedTypes("string")->allowedValues("attacker", "defender");
        $resolver->define("damage")->allowedTypes("int")->required();
        $resolver->define("reflection")->allowedTypes("float")->required();
        $resolver->define("effectSucceeds")->allowedTypes("string", "null")->default(null);
        $resolver->define("effectFails")->allowedTypes("string", "null")->default(null);
        $resolver->define("noEffect")->allowedTypes("string", "null")->default(null);
        $this->context = $resolver->resolve($context);

        $this->damageTarget = $this->context["damageTarget"] === "attacker" ? $this->attacker : $this->defender;
        $this->reflectionTarget = $this->context["reflectionTarget"] === "attacker" ? $this->attacker : $this->defender;
    }

    public function apply(): void
    {
        parent::apply();

        $damage = $this->context["damage"] ?? 0;
        $reflection = $this->context['reflection'];

        if ($this->context["damageTarget"] === "attacker") {
            // The victim is the attacker. We only calculate reflection on negative damage.
            if ($damage > 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["effectFails"] ?? null;
            } elseif ($damage == 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["noEffect"] ?? null;
            } else {
                $this->reflectedDamage = (int)round($reflection * $damage * -1, 0);

                if ($this->reflectedDamage === 0) {
                    $this->message = $this->context["noEffect"] ?? null;
                } else {
                    $this->message = $this->context["effectSucceeds"] ?? null;
                }
            }
        } else {
            // The victim is the attacker. We only calculate reflection on positive damage.
            if ($damage > 0) {
                $this->reflectedDamage = (int)round($reflection * $damage, 0);

                if ($this->reflectedDamage === 0) {
                    $this->message = $this->context["noEffect"] ?? null;
                } else {
                    $this->message = $this->context["effectSucceeds"] ?? null;
                }
            } elseif ($damage == 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["noEffect"] ?? null;;
            } else {
                // Damage is < 0, so goodguy takes damage. This buff cannot reflect.
                $this->reflectedDamage = 0;
                $this->message = $this->context["effectFails"] ?? null;;
            }
        }

        $this->reflectionTarget->damage($this->reflectedDamage);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        if ($this->message === null) {
            return null;
        }

        return new BattleMessage(
            $this->message, [
                "damageTarget" => $this->damageTarget,
                "reflectionTarget" => $this->reflectionTarget,
                "reflectedDamage" => $this->reflectedDamage,
                "attacker" => $this->attacker,
                "defender" => $this->defender,
            ]
        );
    }
}