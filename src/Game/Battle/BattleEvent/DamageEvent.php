<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Damage event gets added for each normal attack
 *
 * @phpstan-type DamageEventCollection ArrayCollection<int, DamageEvent>
 */
class DamageEvent extends AbstractBattleEvent
{
    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{damage: int} $context
     */
    public function __construct(
        private FighterInterface $attacker,
        private FighterInterface $defender,
        protected array $context,
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("damage")->allowedTypes("int")->required();
        $this->context = $resolver->resolve($context);
    }

    public function apply(): void
    {
        parent::apply();

        if ($this->context["damage"] !== 0) {
            $victim = $this->context["damage"] > 0 ? $this->defender : $this->attacker;
            $victim->damage(abs($this->context["damage"]));
        }
    }

    public function getDamage(): int
    {
        return $this->context["damage"];
    }

    public function decorate(): BattleMessage
    {
        parent::decorate();

        if ($this->context["damage"] === 0) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $text = "You try to hit {{ defender }} but MISS!";
            } elseif ($this->defender instanceof CurrentCharacterFighter) {
                $text = "{{ attacker }} tries to hit you but they MISS!";
            } else {
                $text = "{{ attacker }} tries to hit {{ defender }} but they MISS!";
            }
        } elseif ($this->context["damage"] > 0) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $text = "You hit {{ defender }} for {{ damage }} points of damage!";
            } elseif ($this->defender instanceof CurrentCharacterFighter) {
                $text = "{{ attacker }} hits you for {{ damage }} points of damage!";
            } else {
                $text = "{{ attacker }} hits {{ defender }} for {{ damage }} points of damage!";
            }
        } else {
            $this->context["damage"] = abs($this->context["damage"]);
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $text = "You try to hit {{ defender }} but are RIPOSTED for {{ damage }} points of damage!";
            } elseif ($this->defender instanceof CurrentCharacterFighter) {
                $text = "{{ attacker }} tries to hit you but you RIPOSTE for {{ damage }} points of damage!";
            } else {
                $text = "{{ attacker }} tries to hit {{ defender }} but they RIPOSTE for {{ damage}} points of damage!";
            }
        }

        return new BattleMessage(
            $text, [
                "defender" => $this->defender->name,
                "attacker" => $this->attacker->name,
                "damage" => $this->context["damage"],
            ]
        );
    }
}