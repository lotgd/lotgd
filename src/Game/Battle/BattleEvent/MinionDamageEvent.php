<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
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
    protected(set) FighterInterface $target;

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
    }

    public function apply(): void
    {
        parent::apply();

        // If damage is negative, the victim will be _healed_. This is the same behaviour as seen in 0.9.7+jt ext GER 3.
        $victim = $this->context["target"] === "attacker" ? $this->attacker : $this->defender;
        $victim->damage($this->context["damage"]);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        $damage = $this->context["damage"];

        if ($damage < 0) {
            $message = $this->context["effectFails"];
        } elseif ($damage > 0) {
            $message = $this->context["effectSucceeds"];
        } else {
            $message = $this->context["noEffect"];
        }

        if ($message === null) {
            return null;
        }

        return new BattleMessage($message, [
            "attacker" => $this->attacker,
            "defender" => $this->defender,
            "damage" => $damage,
            "target" => $this->context["target"],
        ]);
    }
}