<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;

interface SceneTemplateInterface
{
    public function onSceneLeave(Stage $stage, Action $action, Scene $scene): bool;
    public function onSceneEnter(Stage $stage, Action $action, Scene $scene): bool;
    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void;

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function validateConfiguration(array $config): array;
}