<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Scene;
use LotGD2\Entity\Stage;

trait DefaultSceneTemplate
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

    public static function validateConfiguration(array $config): array {
        return $config;
    }
}