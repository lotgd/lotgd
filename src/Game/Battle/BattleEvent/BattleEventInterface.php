<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;

/**
 * @phpstan-type BattleEventCollection ArrayCollection<int, BattleEventInterface>
 * @template TContext of array = array<string, mixed>
 */
interface BattleEventInterface
{
    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param TContext $context
     */
    public function __construct(
        FighterInterface $attacker,
        FighterInterface $defender,
        array $context,
    );
    public function apply(): void;
    public function decorate(): ?BattleMessage;

    /**
     * @return TContext
     */
    public function getContext(): array;
}