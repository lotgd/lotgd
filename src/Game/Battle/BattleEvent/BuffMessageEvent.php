<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BuffMessageEvent extends AbstractBattleEvent
{

    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{message: string} $context
     */
    public function __construct(
        private readonly FighterInterface $attacker,
        private readonly FighterInterface $defender,
        array $context
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("message")->allowedTypes("string")->required();
        $this->context = $resolver->resolve($context);
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        return new BattleMessage($this->context["message"], [
            "attacker" => $this->attacker,
            "defender" => $this->defender,
        ]);
    }
}