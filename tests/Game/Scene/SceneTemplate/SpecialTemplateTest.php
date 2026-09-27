<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Scene\SceneTemplate\SpecialTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecialTemplate::class)]
#[UsesClass(Action::class)]
class SpecialTemplateTest extends TestCase
{
    /**
     * @return array{
     *     ($characterAsMock is false ? Character|Stub : Character|MockObject),
     *     ($sceneAsMock is false ? Scene|Stub : Scene|MockObject),
     *     ($stageAsMock is false ? Stage|Stub : Stage|MockObject),
     *     ($actionAsMock is false ? Action|Stub : Action|MockObject),
     * }
     * @throws Exception
     */
    private function getTemplateEssentials(
        bool $characterAsMock = false,
        bool $sceneAsMock = false,
        bool $stageAsMock = false,
        bool $actionAsMock = false,
    ): array {
        $character = $characterAsMock ? $this->createMock(Character::class) : $this->createStub(Character::class);
        $scene = $sceneAsMock ? $this->createMock(Scene::class) : $this->createStub(Scene::class);
        $stage = $stageAsMock ? $this->createMock(Stage::class) : $this->createStub(Stage::class);
        $action = $actionAsMock ? $this->createMock(Action::class) : $this->createStub(Action::class);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        return [
            $character,
            $scene,
            $stage,
            $action,
        ];
    }

    private function getSpecialTemplate(
        ?Scene $scene = null,
        ?Stage $stage = null,
        ?Action $action = null,
        ?Character $character = null,
        ?Scene $lastScene = null,
    ): SpecialTemplate {
        $template = new SpecialTemplate();

        if ($stage && $action && $scene) {
            $template->setSceneChangeParameter($stage, $action, $scene, $lastScene);
        }

        return $template;
    }

    public function testConstantSceneTag(): void
    {
        $this->assertSame("lotgd2.special", SpecialTemplate::SceneTag);
    }

    public function testGetTag(): void
    {
        $template = $this->getSpecialTemplate();
        $this->assertSame("lotgd2.special", $template->getTag());
    }

    public function testSetSceneChangeParameterAndPropertyAccess(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $lastScene = $this->createStub(Scene::class);

        $template = new SpecialTemplate();
        $return = $template->setSceneChangeParameter($stage, $action, $scene, $lastScene);

        $this->assertSame($template, $return);
        $this->assertSame($stage, $template->stage);
        $this->assertSame($action, $template->action);
        $this->assertSame($scene, $template->scene);
        $this->assertSame($lastScene, $template->lastScene);
        $this->assertSame($character, $template->character);
    }

    public function testSetSceneChangeParameterWithNullLastScene(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();

        $template = new SpecialTemplate();
        $template->setSceneChangeParameter($stage, $action, $scene, null);

        $this->assertNull($template->lastScene);
    }

    public function testOnSceneEnterReturnsFalse(): void
    {
        $template = $this->getSpecialTemplate();
        $this->assertFalse($template->onSceneEnter());
    }

    public function testOnSceneLeaveReturnsFalse(): void
    {
        $template = $this->getSpecialTemplate();
        $this->assertFalse($template->onSceneLeave());
    }

    public function testOnSceneChangeDoesNothing(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $template = $this->getSpecialTemplate($scene, $stage, $action, $character);

        $template->onSceneChange();
        $this->expectNotToPerformAssertions();
    }

    public function testOnSceneChangeWithEmptyConfiguration(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $template = $this->getSpecialTemplate($scene, $stage, $action, $character);
        $template->onSceneChange();

        $this->assertSame([], $scene->templateConfig);
    }
}
