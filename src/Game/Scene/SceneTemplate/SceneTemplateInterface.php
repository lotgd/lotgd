<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;

/**
 * @template TemplateConfiguration of array<string, mixed>
 */
interface SceneTemplateInterface
{
    protected(set) Stage $stage {
        get;
        set;
    }

    protected(set) Action $action {
        get;
        set;
    }

    protected(set) Scene $scene {
        get;
        set;
    }

    protected(set) ?Scene $lastScene {
        get;
        set;
    }

    protected(set) Character $character {
        get;
        set;
    }

    public function onSceneLeave(): bool;
    public function onSceneEnter(): bool;
    public function onSceneChange(): void;

    /**
     * @return self<TemplateConfiguration>
     */
    public function setSceneChangeParameter(Stage $stage, Action $action, Scene $currentScene, ?Scene $lastScene=null): self;
}
