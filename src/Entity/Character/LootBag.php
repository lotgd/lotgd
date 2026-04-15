<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;

class LootBag
{
    /** @var array<string, LootPosition> */
    private array $positions = [];

    public function __construct(
        private bool $locked = false,
        protected(set) DiceBagInterface $diceBag = new DiceBag(),
    ) {

    }

    public function lock(): void
    {
        $this->locked = true;
        array_walk($this->positions, function (LootPosition $position, string $id): true {
            $this->positions[$id] = $position->getLockedCopy();
            return true;
        });
    }

    public function add(LootPosition $position): void
    {
        if ($this->locked) {
            throw new \LogicException("You can't add a new loot position to a closed loot bag. "
                ."You are probably trying to add a new loot position inside the a LootBagClaim event.");
        }

        $this->positions[$position->id] = $position;
    }

    public function get(string $id): ?LootPosition
    {
        return $this->positions[$id] ?? null;
    }
}