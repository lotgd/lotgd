<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DeathEvent gets added to the battle log if a fighter dies.
 */
class DeathEvent extends AbstractBattleEvent
{
    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{victim: FighterInterface} $context
     */
    public function __construct(
        FighterInterface $attacker,
        FighterInterface $defender,
        /** @var  */
        protected array $context,
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("victim")->allowedTypes(FighterInterface::class)->required();
        $this->context = $resolver->resolve($context);
    }

    public function apply(): void
    {
    }

    public function decorate(): ?BattleMessage
    {
        if ($this->context["victim"] instanceof CurrentCharacterFighter) {
            return new BattleMessage("You died.", []);
        } else {
            return new BattleMessage("You defeated {{ victim }}.", ["victim" => $this->context["victim"]->name]);
        }
    }
}