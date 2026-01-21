<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Game\Error\BattleEventError;

abstract class AbstractBattleEvent implements BattleEventInterface
{
    private bool $applied = false;
    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Applies the event.
     * @throws BattleEventError
     */
    public function apply(): void
    {
        if ($this->applied === true) {
            throw new BattleEventError("Cannot apply a battle event more than once.");
        }

        $this->applied = true;
    }

    /**
     * Returns a string describing the event.
     * @throws BattleEventError
     * @return BattleMessage
     */
    public function decorate(): ?BattleMessage
    {
        if ($this->applied === false) {
            throw new BattleEventError("Battle event needs to get applied before decoration.");
        }

        return null;
    }
}