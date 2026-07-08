<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;

trait DefaultSceneTemplate
{
    // Cannot be anything else than private due to conflict with DefaultSceneTemplate
    protected(set) ?Stage $stage {
        get => $this->stage ?? null;
        set(?Stage $value) => $value;
    }

    protected(set) Action $action {
        get => $this->action;
        set(Action $value) => $value;
    }

    protected(set) Scene $scene {
        get => $this->scene;
        set(Scene $value) => $value;
    }
    protected(set) ?Scene $lastScene {
        get => $this->lastScene;
        set(?Scene $value) => $value;
    }

    protected(set) Character $character {
        get => $this->character;
        set(Character $value) => $value;
    }

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