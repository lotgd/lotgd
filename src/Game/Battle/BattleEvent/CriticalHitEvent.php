<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriticalHitEvent extends AbstractBattleEvent
{
    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{criticalAttackValue: int} $context
     */
    public function __construct(
        private FighterInterface $attacker,
        private FighterInterface $defender,
        protected array $context,
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("criticalAttackValue")->allowedTypes("int")->required();
        $this->context = $resolver->resolve($context);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        $criticalAttack = $this->context["criticalAttackValue"];
        $normalAttack = $this->attacker->attack;
        $context = [
            "attacker" => $this->attacker->name,
            "defender" => $this->defender->name,
        ];

        if ($criticalAttack > $normalAttack*4) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $message = "You execute a MEGA power move!!!";
            } else {
                $message = "{{ attacker }} executes a MEGA power move!!!";
            }
        } elseif ($criticalAttack > $normalAttack * 3) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $message = "You execute a DOUBLE power move!!!";
            } else {
                $message = "{{ attacker }} executes a DOUBLE power move!!!";
            }
        } elseif ($criticalAttack > $normalAttack * 2) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $message = "You execute a power move!!!";
            } else {
                $message = "{{ attacker }} executes a DOUBLE power move!!!";
            }
        } elseif ($criticalAttack > $normalAttack * 1.25) {
            if ($this->attacker instanceof CurrentCharacterFighter) {
                $message = "You execute a minor power move!";
            } else {
                $message = "{{ attacker }} executes a minor power move!";
            }
        } else {
            return null;
        }

        return new BattleMessage($message, $context);
    }
}