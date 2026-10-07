<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;

/**
 * LootBag is a collection of LootPosition objects.
 *
 * A LootBag can be created once an enemy is defeated. There are always two rounds of events:
 *   One where loot positions are added, and
 *   one where loot positions are claimed.
 *
 * During the claim round, the LootBag is locked and no new loot positions can be added. For each individual
 *   loot position, a new, locked copy of that position is created.
 */
class LootBag
{
    /** @var array<string, LootPosition> */
    private(set) array $positions = [];

    public function __construct(
        private(set) bool $locked = false,
        private(set) DiceBagInterface $diceBag = new DiceBag(),
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