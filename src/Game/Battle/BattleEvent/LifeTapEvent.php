<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type LifeTapContext array{
 *       damageVictim: "attacker"|"defender",
 *       healTarget: "defender"|"attacker",
 *       damage: int,
 *       lifeTap: float,
 *       effectSucceeds?: ?string,
 *       effectFails?: ?string,
 *       noEffect?: ?string,
 *  }
 * @extends AbstractBattleEvent<LifeTapContext>
 */
class LifeTapEvent extends AbstractBattleEvent
{
    private(set) ?string $message = null {
        get => $this->message;
        set(null|string $value) {
            $this->message = $value;
        }
    }

    private(set) int $healedDamage = 0 {
        get => $this->healedDamage;
        set(int $value) {
            $this->healedDamage = $value;
        }
    }

    /**
     * Target of the original attack
     * @var FighterInterface
     */
    private(set) FighterInterface $damageVictim {
        get => $this->damageVictim;
        set(FighterInterface $value) {
            $this->damageVictim = $value;
        }
    }

    /**
     * Target to heal based of the damage.
     * @var FighterInterface
     */
    private(set) FighterInterface $healTarget {
        get => $this->healTarget;
        set(FighterInterface $value) {
            $this->healTarget = $value;
        }
    }

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param LifeTapContext $context
     */
    public function __construct(
        private FighterInterface $attacker,
        private FighterInterface $defender,
        array $context,
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("damageVictim")->allowedTypes("string")->allowedValues("attacker", "defender");
        $resolver->define("healTarget")->allowedTypes("string")->allowedValues("defender", "attacker");
        $resolver->define("damage")->allowedTypes("int")->required();
        $resolver->define("lifeTap")->allowedTypes("float")->required();
        $resolver->define("effectSucceeds")->allowedTypes("string", "null")->default(null);
        $resolver->define("effectFails")->allowedTypes("string", "null")->default(null);
        $resolver->define("noEffect")->allowedTypes("string", "null")->default(null);
        $this->context = $resolver->resolve($context);

        $this->damageVictim = $this->context["damageVictim"] === "attacker" ? $this->attacker : $this->defender;
        $this->healTarget = $this->context["healTarget"] === "attacker" ? $this->attacker : $this->defender;
    }

    public function apply(): void
    {
        parent::apply();

        $damage = $this->context["damage"] ?? 0;
        $lifeTap = $this->context['lifeTap'];

        if ($this->context["damageVictim"] === "attacker") {
            /*
             * Damage victim is _attacker_, heal receiver is the _defender_.
             *  - positive damage is damage towards the defender. If the target of the buff is the attacker, the effect will fail.
             *  - negative damage is a riposte, and thus damage caused to the attacker. We will life tap this.
             *  - zero damage, zero effect
             */
            if ($damage > 0) {
                $this->healedDamage = 0;
                $this->message = $this->context["effectFails"];
            } elseif ($damage < 0) {
                $this->healedDamage = $this->getCappedHealth(-$damage, $lifeTap);

                if ($this->healedDamage === 0) {
                    $this->message = $this->context["noEffect"];
                } else {
                    $this->message = $this->context["effectSucceeds"];
                }
            } else {
                $this->healedDamage = 0;
                $this->message = $this->context["noEffect"];
            }
        } else {
            /*
             * Damage victim is _defender_, heal receiver is the _attacker_.
             *  - positive damage is damage towards the defender. We will life tap this.
             *  - negative damage is a riposte, and thus damage caused to the attacker. The effect will fail.
             *  - zero damage, zero effect
             */
            if ($damage > 0) {
                $this->healedDamage = $this->getCappedHealth($damage, $lifeTap);

                if ($this->healedDamage === 0) {
                    $this->message = $this->context["noEffect"];
                } else {
                    $this->message = $this->context["effectSucceeds"];
                }
            } elseif ($damage < 0) {
                $this->healedDamage = 0;
                $this->message = $this->context["effectFails"];
            } else {
                $this->healedDamage = 0;
                $this->message = $this->context["noEffect"];
            }
        }

        $this->healTarget->damage(-$this->healedDamage);
    }

    private function getCappedHealth(int $damage, float $lifeTap): int
    {
        $healedDamage = (int)round($damage * $lifeTap, 0);
        $healthMissing = $this->healTarget->maxHealth - $this->healTarget->health;

        if ($healthMissing <= 0) {
            // If there is no health missing, healed damage is 0
            $healedDamage = 0;
        } elseif ($healthMissing < $healedDamage) {
            // If there is less health missing than what is healed, we put a cap on it.
            $healedDamage = $healthMissing;
        }

        return $healedDamage;
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        if ($this->message === null) {
            return null;
        }

        return new BattleMessage(
            $this->message, [
                "damageVictim" => $this->damageVictim,
                "healTarget" => $this->healTarget,
                "heal" => $this->healedDamage,
                "attacker" => $this->attacker,
                "defender" => $this->defender,
            ]
        );
    }
}