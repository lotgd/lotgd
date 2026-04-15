<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LifeTapEvent extends AbstractBattleEvent
{
    private ?string $message = null;
    private int $healedDamage = 0;
    private FighterInterface $victim;

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{
     *      target: "attacker"|"defender",
     *      damage: int,
     *      lifeTap: float,
     *      effectSucceeds?: ?string,
     *      effectFails?: ?string,
     *      noEffect?: ?string,
     * } $context
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

        if ($this->context["victim"] === "attacker") {
            if ($damage > 0) {
                // Damage is > 0, badguy takes damage. Goodguy lifetap works only upon damage to the goodguy.
                $this->healedDamage = 0;
                $this->message = $this->context["effectFailsMessage"];
            } elseif ($damage < 0) {
                // Damage is < 0, goodguy takes damage. We act upon this.
                $this->healedDamage = (int)round($damage * -$lifeTap, 0);

                if ($this->healedDamage === 0) {
                    $this->message = $this->context["noEffectMessage"];
                } else {
                    $this->message = $this->context["effectSucceedsMessage"];
                }
            } else {
                $this->healedDamage = 0;
                $this->message = $this->context["noEffectMessage"];
            }
        } else {

            if ($damage > 0) {
                // Damage is > 0, goodguy takes damage. We act upon this to heal goodguy.
                $this->healedDamage = (int)round($damage * $lifeTap, 0);

                if ($this->healedDamage === 0) {
                    $this->message = $this->context["noEffectMessage"];
                } else {
                    $this->message = $this->context["effectSucceedsMessage"];
                }
            } elseif ($damage < 0) {
                // Damage is < 0, goodguy takes damage. Badguy lifetap works only upon damage to the goodguy.
                $this->healedDamage = 0;
                $this->message = $this->context["effectFailsMessage"];
            } else {
                $this->healedDamage = 0;
                $this->message = $this->context["noEffectMessage"];
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
                "attacker" => $this->attacker,
                "defender" => $this->defender,
            ]
        );
    }
}