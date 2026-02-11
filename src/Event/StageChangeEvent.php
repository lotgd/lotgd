<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use Symfony\Contracts\EventDispatcher\Event;

final class StageChangeEvent extends Event
{
    private(set) readonly Character $character;
    private(set) bool $stopRender = false;

    public function __construct(
        private(set) readonly Stage $stage,
        private(set) readonly Action $action,
        private(set) readonly Scene $scene,
    ) {
        $this->character = $stage->owner;
    }

    public function setStopRender(bool $stopRender = true): void
    {
        $this->stopRender = $stopRender;
    }
}
