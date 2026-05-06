<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type LifeTapContext array{
 *       target: "attacker"|"defender",
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
    private ?string $message = null;
    private int $healedDamage = 0;
    private FighterInterface $victim;

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
        $resolver->define("target")->allowedTypes("string")->allowedValues("attacker", "defender");
        $resolver->define("damage")->allowedTypes("int")->required();
        $resolver->define("lifeTap")->allowedTypes("float")->required();
        $resolver->define("effectSucceeds")->allowedTypes("string", "null")->default(null);
        $resolver->define("effectFails")->allowedTypes("string", "null")->default(null);
        $resolver->define("noEffect")->allowedTypes("string", "null")->default(null);
        $this->context = $resolver->resolve($context);

        $this->victim = $this->context["target"] === "attacker" ? $this->attacker : $this->defender;
    }

    public function apply(): void
    {
        parent::apply();

        $damage = $this->context["damage"] ?? 0;
        $lifeTap = $this->context['lifeTap'];

        if ($this->context["target"] === "attacker") {
            if ($damage > 0) {
                // Damage is > 0, badguy takes damage. Goodguy lifetap works only upon damage to the goodguy.
                $this->healedDamage = 0;
                $this->message = $this->context["effectFails"];
            } elseif ($damage < 0) {
                // Damage is < 0, goodguy takes damage. We act upon this.
                $this->healedDamage = (int)round($damage * -$lifeTap, 0);

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
            if ($damage > 0) {
                // Damage is > 0, goodguy takes damage. We act upon this to heal goodguy.
                $this->healedDamage = (int)round($damage * $lifeTap, 0);

                if ($this->healedDamage === 0) {
                    $this->message = $this->context["noEffect"];
                } else {
                    $this->message = $this->context["effectSucceeds"];
                }
            } elseif ($damage < 0) {
                // Damage is < 0, goodguy takes damage. Badguy lifetap works only upon damage to the goodguy.
                $this->healedDamage = 0;
                $this->message = $this->context["effectFails"];
            } else {
                $this->healedDamage = 0;
                $this->message = $this->context["noEffect"];
            }
        }

        $this->victim->damage(-$this->healedDamage);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        if ($this->message === null) {
            return null;
        }

        return new BattleMessage(
            $this->message, [
                "victim" => $this->victim,
                "heal" => $this->healedDamage,
                "damage" => $this->healedDamage,
                "attacker" => $this->attacker,
                "defender" => $this->defender,
            ]
        );
    }
}