<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use LogicException;

/**
 * LootPosition represents a single loot position.
 *
 * It takes an identifier (so other modules can reference it) and loot (an arbitrary array).
 *
 * The loot itself depends heavily on which module (or core part) created the loot position and is specific to the id.
 */
class LootPosition
{
    protected(set) bool $locked = false {
        get => $this->locked;
        set => $this->locked = $value;
    }

    /**
     * @param string $id
     * @param array<string, mixed> $loot
     */
    public function __construct(
        protected(set) string $id {
            get => $this->id;
            set => $this->id = $value;
        },

        public array $loot {
            get => $this->loot;
            set {
                if ($this->locked) {
                    throw new LogicException("You cannot change the contents of a locked loot position.");
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