<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Mapped\Character;
use Symfony\Contracts\EventDispatcher\Event;

class CharacterChangeEvent extends Event
{
    /**
     * @param Character $character
     * @param Character $characterBefore
     */
    public function __construct(
        public readonly Character $character,
        public readonly Character $characterBefore,
    ) {

    }
}
