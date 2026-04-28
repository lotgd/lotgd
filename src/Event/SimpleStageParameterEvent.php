<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Random\DiceBagAwareInterface;
use LotGD2\Game\Random\DiceBagAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class SimpleStageParameterEvent extends Event
{
    protected(set) Character $character;

    /**
     * @var array<string, mixed>
     */
    public array $params;

    public function __construct(
        protected(set) Stage $stage,
        protected(set) Action $action,
        protected(set) Scene $scene,
        mixed ... $params
    ) {
        $this->character = $stage->owner;
        $this->params = $params;
    }
}
