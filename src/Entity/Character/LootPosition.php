<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

class LootPosition
{
    private bool $locked = false;

    /**
     * @param string $id
     * @param array<string, mixed> $loot
     */
    public function __construct(
        protected(set) string $id,
        public array $loot {
            get => $this->loot;
            set {
                if ($this->locked) {
                    throw new \LogicException("You cannot change the contents of a locked loot position.");
                }

                $this->loot = $value;
            }
        },
    ) {

    }

    public function getLockedCopy(): LootPosition
    {
        $self = new self($this->id, $this->loot);
        $self->locked = true;
        return $self;
    }
}