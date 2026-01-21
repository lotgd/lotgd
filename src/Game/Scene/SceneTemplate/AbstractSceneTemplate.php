<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Scene;
use LotGD2\Entity\Stage;

abstract class AbstractSceneTemplate implements SceneTemplateInterface
{
    public function onSceneLeave(Stage $stage, Action $action, Scene $scene): bool
    {
        return false;
    }

    public function onSceneEnter(Stage $stage, Action $action, Scene $scene): bool
    {
        return false;
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
    }
}