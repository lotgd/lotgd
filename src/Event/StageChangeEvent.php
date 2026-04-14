<?php
declare(strict_types=1);

namespace LotGD2\Event;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use Symfony\Contracts\EventDispatcher\Event;

class StageChangeEvent extends Event
{
    public Stage $stage {
        get => $this->_internal["stage"];
    }
    public Action $action {
        get => $this->_internal["action"];
    }
    public Scene $scene {
        get => $this->_internal["scene"];
    }
    public Character $character {
        get => $this->_internal["character"];
    }
    public Character $characterBefore {
        get => $this->_internal["characterBefore"];
    }
    private(set) bool $stopRender = false;
    /**
     * Clutch as PHPUnit 12.5 does not yet support mocking getters in private(set)
     * @var array{
     *     character: Character,
     *     characterBefore: Character,
     *     stage: Stage,
     *     action: Action,
     *     scene: Scene,
     * }
     */
    private array $_internal;

    public function __construct(
        Stage $stage,
        Action $action,
        Scene $scene,
    ) {
        $this->_internal = [
            "stage" => $stage,
            "action" => $action,
            "scene" => $scene,
            "character" => $stage->owner,
            "characterBefore" => clone $stage->owner,
        ];
    }

    public function setStopRender(bool $stopRender = true): void
    {
        $this->stopRender = $stopRender;
        $this->stopPropagation();
    }

    /**
     * Helper method to add actions to the stage inside the event.
     *
     * Sets the scene id (if not set yet) of the scene id from the action taken before the
     * event fired.
     * @param string|ActionGroup $actionGroup
     * @param Action $action
     * @return $this
     */
    public function addAction(string|ActionGroup $actionGroup, Action $action): self
    {
        if ($action->sceneId === null) {
            $action->sceneId = $this->action->sceneId;
        }

        $this->stage->addAction($actionGroup, $action);
        return $this;
    }
}
