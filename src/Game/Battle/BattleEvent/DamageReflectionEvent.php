<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamageReflectionEvent extends AbstractBattleEvent
{
    private ?string $message = null;
    private int $reflectedDamage = 0;
    private FighterInterface $victim;

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{
     *      target: "attacker"|"defender",
     *      damage: int,
     *      reflection: float,
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
        $resolver->define("reflection")->allowedTypes("float")->required();
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
        $reflection = $this->context['reflection'];

        if ($this->context["victim"] === "attacker") {
            // The victim is the attacker. We only calculate reflection on negative damage.
            if ($damage > 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["effectFailsMessage"] ?? null;
            } elseif ($damage == 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["noEffectMessage"] ?? null;
            } else {
                $this->reflectedDamage = (int)round($reflection * $damage * -1, 0);

                if ($this->reflectedDamage === 0) {
                    $this->message = $this->context["noEffectMessage"] ?? null;
                } else {
                    $this->message = $this->context["effectSucceedsMessage"] ?? null;
                }
            }
        } else {
            // The victim is the attacker. We only calculate reflection on positive damage.
            if ($damage > 0) {
                $this->reflectedDamage = (int)round($reflection * $damage, 0);

                if ($this->reflectedDamage === 0) {
                    $this->message = $this->context["noEffectMessage"] ?? null;
                } else {
                    $this->message = $this->context["effectSucceedsMessage"] ?? null;
                }
            } elseif ($damage == 0) {
                $this->reflectedDamage = 0;
                $this->message = $this->context["noEffectMessage"] ?? null;;
            } else {
                // Damage is < 0, so goodguy takes damage. This buff cannot reflect.
                $this->reflectedDamage = 0;
                $this->message = $this->context["effectFailsMessage"] ?? null;;
            }
        }

        $this->victim->damage($this->reflectedDamage);
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
                "damage" => $this->reflectedDamage,
                "attacker" => $this->attacker,
                "defender" => $this->defender,
            ]
        );
    }
}