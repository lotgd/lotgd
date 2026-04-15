<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Character\LootBag;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use Symfony\Contracts\EventDispatcher\Event;
use ValueError;

class LootBagEvent extends Event
{
    protected(set) Character $character;

    /**
     * @param BattleState $battleState
     * @param LootBag $lootBag
     * @param Stage|null $stage
     */
    public function __construct(
        protected(set) readonly BattleState $battleState,
        protected(set) readonly LootBag $lootBag = new LootBag(),
        protected(set) readonly ?Stage $stage = null,
    ) {
        if ($this->battleState->character === null) {
            throw new ValueError("BattleState's character must be set.");
        }

        $this->character = $this->battleState->character;
    }
}