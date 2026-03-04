<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;

/**
 * @template TemplateConfiguration of array<string, mixed>
 */
interface SceneTemplateInterface
{
    public function onSceneLeave(): bool;
    public function onSceneEnter(): bool;
    public function onSceneChange(): void;

    /**
     * @return self<TemplateConfiguration>
     */
    public function setSceneChangeParameter(Stage $stage, Action $action, Scene $currentScene, ?Scene $lastScene=null): self;
}
