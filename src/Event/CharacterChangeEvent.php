<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use Symfony\Contracts\EventDispatcher\Event;

class CharacterChangeEvent extends Event
{
    /**
     * @param Character $character A mapped entity of the character
     * @param Character $characterBefore A detached clone of the character entity containing the entity before the stange. Can be used to calculate delta increases.
     * @param ?Stage $stage Optionally a stage to provide outout. Presence of stage is not guarantueed.
     */
    public function __construct(
        public readonly Character $character,
        public readonly Character $characterBefore,
        public readonly ?Stage $stage = null,
    ) {

    }
}
