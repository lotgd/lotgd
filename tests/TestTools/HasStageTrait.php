<?php
declare(strict_types=1);

namespace LotGD2\Tests\TestTools;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-require-extends TestCase
 */
trait HasStageTrait
{
    /**
     * @param bool $stageMock
     * @param bool $sceneMock
     * @param bool $actionMock
     * @param bool $characterMock
     * @return array{
     *     ($stageMock is true ? Stage&MockObject : Stage&Stub),
     *     ($sceneMock is true ? Scene&MockObject : Scene&Stub),
     *     ($actionMock is true ? Action&MockObject : Action&Stub),
     *     ($characterMock is true ? Character&MockObject : Character&Stub)
     * }
     */
    private function getDefaultStageObjects(
        bool $stageMock = false,
        bool $sceneMock = false,
        bool $actionMock = false,
        bool $characterMock = false,
        ?array $sceneConfig = null,
        ?InvocationOrder $sceneConfigExpects = null,
    ): array {
        $stage = $stageMock ? $this->createMock(Stage::class) : $this->createStub(Stage::class);
        $action = $actionMock ? $this->createMock(Action::class) : $this->createStub(Action::class);
        $scene = $sceneMock ? $this->createMock(Scene::class) : $this->createStub(Scene::class);
        $character = $characterMock ? $this->createMock(Character::class) : $this->createStub(Character::class);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->method(PropertyHook::get("scene"))->willReturn($scene);

        if ($sceneConfig) {
            if ($sceneConfigExpects and $sceneMock) {
                $scene
                    ->expects($sceneConfigExpects)
                    ->method(PropertyHook::get("templateConfig"))
                    ->willReturn($sceneConfig);
            } else {
                $scene
                    ->method(PropertyHook::get("templateConfig"))
                    ->willReturn($sceneConfig);
            }
        }

        return [$stage, $scene, $action, $character];
    }
}