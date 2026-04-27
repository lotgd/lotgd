<?php
declare(strict_types=1);

namespace LotGD2\Event;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\BuffHandler;
use Symfony\Contracts\EventDispatcher\Event;

class BattleSkillActivationEvent extends Event
{
    public function __construct(
        public readonly Character $character,
        public readonly BuffHandler $buff,
        public readonly string $skillName,
    ) {
    }
}