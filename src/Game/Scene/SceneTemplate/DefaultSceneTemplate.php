<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;

trait DefaultSceneTemplate
{
    private Stage $stage;
    private Action $action;
    private Scene $scene;
    private ?Scene $lastScene;
    private Character $character;

    public function setSceneChangeParameter(Stage $stage, Action $action, Scene $currentScene, ?Scene $lastScene=null): self
    {
        $this->stage = $stage;
        $this->action = $action;
        $this->scene = $currentScene;
        $this->lastScene = $lastScene;
        $this->character = $stage->owner;

        return $this;
    }

    public function onSceneLeave(): bool
    {
        return false;
    }

    public function onSceneEnter(): bool
    {
        return false;
    }

    public function onSceneChange(): void
    {
    }
}