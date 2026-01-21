<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(SceneRenderer::class)]
#[UsesClass(SceneRepository::class)]
#[UsesClass(Scene::class)]
#[UsesClass(Character::class)]
#[UsesClass(Stage::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(SceneConnection::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Action::class)]
class SceneRendererTest extends TestCase
{
    private SceneRenderer $renderer;
    private SceneRepository&MockObject $sceneRepository;
    private DiceBag&MockObject $diceBag;

    protected function setUp(): void
    {
        $this->sceneRepository = $this->createMock(SceneRepository::class);
        $this->diceBag = $this->createMock(DiceBag::class);

        $this->renderer = new SceneRenderer(
            $this->sceneRepository,
            $this->diceBag,
        );
    }

    private function getSceneMock(string $title, string $description): Scene&MockObject
    {
        $scene = $this->createMock(Scene::class);
        $scene->title = $title;
        $scene->description = $description;

        return $scene;
    }

    /**
     * Test if defaults are set as expected
     */
    public function testSetDefault(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");

        $sceneRepository = $this->createMock(SceneRepository::class);
        $sceneRepository->method("getDefaultScene")->willReturn($scene);

        $character = $this->createMock(Character::class);

        $diceBag = $this->createMock(DiceBag::class);

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag);

        $stage = $sceneRenderer->renderDefault($character);

        $this->assertSame($scene, $stage->getScene());
    }

    /**
     * Test if the stage is set up correctly
     */
    public function testIfStageIsSetCorrectly(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");

        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createMock(DiceBag::class);

        $stage = new Stage();

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag);
        $stage = $sceneRenderer->render($stage, $scene);

        $this->assertNotNull($stage);
        $this->assertSame("Test Scene", $stage->getTitle());
        $this->assertSame("A nice scenery", $stage->getDescription());

        $actionGroups = $stage->getActionGroups();
        $filtered = array_filter($actionGroups, fn (ActionGroup $actionGroup) => $actionGroup->getId() === ActionGroup::EMPTY);
        $this->assertCount(1, $filtered);
        $filtered = array_filter($actionGroups, fn (ActionGroup $actionGroup) => $actionGroup->getId() === ActionGroup::HIDDEN);
        $this->assertCount(1, $filtered);
    }

    /**
     * Test if actions are expected if the scene is the source of a connection
     */
    public function testIfActionIsCreatedAsExpectedIfSceneIsSourceScene(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");
        $otherScene = $this->getSceneMock("Other Scene", "A new scenery");
        $otherScene->method(PropertyHook::get("id"))->willReturn(13);
        $sceneConnection = $this->createMock(SceneConnection::class);
        $sceneConnection->sourceScene = $scene;
        $sceneConnection->targetScene = $otherScene;
        $sceneConnection->sourceLabel = $otherScene->title;
        $sceneConnection->targetLabel = $scene->title;

        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = new DiceBag();

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection);

        $this->assertSame($otherScene->title, $action->title);
        $this->assertSame(13, $action->sceneId);
    }

    /**
     * Test if actions are expected if the scene is the target of a connection
     */
    public function testIfActionIsCreatedAsExpectedIfSceneIsTargetScene(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");
        $otherScene = $this->getSceneMock("Other Scene", "A new scenery");
        $otherScene->method(PropertyHook::get("id"))->willReturn(13);
        $sceneConnection = $this->createMock(SceneConnection::class);
        $sceneConnection->targetScene = $scene;
        $sceneConnection->sourceScene = $otherScene;
        $sceneConnection->targetLabel = $otherScene->title;
        $sceneConnection->sourceLabel = $scene->title;

        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = new DiceBag();

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection);

        $this->assertSame($otherScene->title, $action->title);
        $this->assertSame(13, $action->sceneId);
    }

    /**
     * Test if connections not related to the scene still end in ations
     */
    public function testIfActionIsCreatedAsExpectedEvenIfConnectionIsDoesNotConnectScene(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");
        $sourceScene = $this->getSceneMock("Source Scene", "The original scenery");
        $sourceScene->method(PropertyHook::get("id"))->willReturn(13);
        $targetScene = $this->getSceneMock("Target Scene", "The target scenery");
        $targetScene->method(PropertyHook::get("id"))->willReturn(33);

        $sceneConnection = $this->createMock(SceneConnection::class);
        $sceneConnection->targetScene = $targetScene;
        $sceneConnection->sourceScene = $sourceScene;
        $sceneConnection->targetLabel = $sourceScene->title;
        $sceneConnection->sourceLabel = $targetScene->title;
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = new DiceBag();

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection);

        $this->assertStringStartsWith("#invalidConnection", $action->title);
        $this->assertNull($action->sceneId);
    }

    /**
     * Test that addActions handles empty action groups correctly
     */
    public function testAddActionsWithEmptyScene(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection());
        $scene->method('getConnections')->willReturn(new ArrayCollection());

        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->getActionGroups();
        $this->assertCount(0, $actionGroups);;
    }

    /**
     * Test that addActions creates action groups from scene action groups
     */
    public function testAddActionsCreatesActionGroupsFromSceneActionGroups(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Create a mock scene action group
        $sceneActionGroup = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup->method(PropertyHook::get("title"))->willReturn('Explore');
        $sceneActionGroup->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection());

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup]));
        $scene->method('getConnections')->willReturn(new ArrayCollection());

        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->getActionGroups();
        $this->assertCount(1, $actionGroups);
        $this->assertArrayHasKey("lotgd.actionGroup.custom.13", $actionGroups);;
        $this->assertSame('lotgd.actionGroup.custom.13', $actionGroups["lotgd.actionGroup.custom.13"]->getId());
        $this->assertSame('Explore', $actionGroups["lotgd.actionGroup.custom.13"]->getTitle());
        $this->assertSame(10, $actionGroups["lotgd.actionGroup.custom.13"]->getWeight());
    }

    /**
     * Test that addActions adds connections to action groups
     */
    public function testAddActionsAddsConnectionsToActionGroups(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Create a mock connection
        $connection = $this->createMock(SceneConnection::class);
        $connection->method(PropertyHook::get("id"))->willReturn(1);
        $connection->sourceScene = $scene;
        $connection->sourceLabel = 'Go North';

        // Create target scene
        $targetScene = $this->createMock(Scene::class);
        $targetScene->method(PropertyHook::get("id"))->willReturn(2);
        $connection->targetScene = $targetScene;

        // Create a mock scene action group
        $sceneActionGroup = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup->method(PropertyHook::get("title"))->willReturn('Explore');
        $sceneActionGroup->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection([$connection]));

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup]));
        $scene->method('getConnections')->willReturn(new ArrayCollection());

        $this->diceBag->method('getRandomString')->willReturn('action-id');

        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->getActionGroups();
        $this->assertCount(1, $actionGroups);
        $this->assertArrayHasKey("lotgd.actionGroup.custom.13", $actionGroups);
        $this->assertCount(1, $actionGroups["lotgd.actionGroup.custom.13"]->getActions());

        $action = array_values($actionGroups["lotgd.actionGroup.custom.13"]->getActions())[0];
        $this->assertSame('Go North', $action->title);
        $this->assertSame(2, $action->sceneId);
    }

    /**
     * Test that duplicate connections are not added multiple times
     */
    public function testAddActionsDuplicateConnectionsNotAdded(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Create a mock connection
        $connection = $this->createMock(SceneConnection::class);
        $connection->method(PropertyHook::get("id"))->willReturn(1);
        $connection->sourceScene = $scene;
        $connection->sourceLabel = 'Go North';

        $targetScene = $this->createMock(Scene::class);
        $targetScene->method(PropertyHook::get("id"))->willReturn(2);
        $connection->targetScene = $targetScene;

        // Add the same connection to action group
        $sceneActionGroup = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup->method(PropertyHook::get("title"))->willReturn('Explore');
        $sceneActionGroup->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection([$connection]));

        // And also include it in visible connections
        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup]));
        $scene->method('getConnections')->willReturn(new ArrayCollection([$connection]));

        $this->diceBag->method('getRandomString')->willReturn('action-id');

        $this->renderer->addActions($stage, $scene);

        // The connection should only be added once
        $actionGroups = $stage->getActionGroups();
        $this->assertCount(1, $actionGroups);
        $this->assertArrayHasKey("lotgd.actionGroup.custom.13", $actionGroups);
        $this->assertCount(1, $actionGroups["lotgd.actionGroup.custom.13"]->getActions());
    }

    /**
     * Test that visible-only connections not in action groups are added to empty group
     */
    public function testAddActionsVisibleConnectionsAddedToEmptyGroup(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Create a standalone connection (not in any action group)
        $connection = $this->createMock(SceneConnection::class);
        $connection->method(PropertyHook::get("id"))->willReturn(2);
        $connection->sourceScene = $scene;
        $connection->sourceLabel = 'Go South';

        $targetScene = $this->createMock(Scene::class);
        $targetScene->method(PropertyHook::get("id"))->willReturn(3);
        $connection->targetScene = $targetScene;

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection());
        $scene->method('getConnections')->willReturn(new ArrayCollection([$connection]));

        $this->diceBag->method('getRandomString')->willReturn('action-id-2');

        $this->renderer->addDefaultActionGroups($stage);
        $this->renderer->addActions($stage, $scene);

        $actions = $stage->getActionGroups()[ActionGroup::EMPTY]->getActions();
        $action = array_values($actions)[0];
        $this->assertCount(1, $actions);
        $this->assertSame('Go South', $action->title);
        $this->assertSame(3, $action->sceneId);
    }

    /**
     * Test that connections from both action groups and visible connections are handled correctly
     */
    public function testAddActionsMixedActionGroupsAndVisibleConnections(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Connection in action group
        $connection1 = $this->createMock(SceneConnection::class);
        $connection1->method(PropertyHook::get("id"))->willReturn(1);
        $connection1->sourceScene = $scene;
        $connection1->sourceLabel = 'Go North';

        $targetScene1 = $this->createMock(Scene::class);
        $targetScene1->method(PropertyHook::get("id"))->willReturn(20);
        $connection1->targetScene = $targetScene1;

        // Connection in visible connections only
        $connection2 = $this->createMock(SceneConnection::class);
        $connection2->method(PropertyHook::get("id"))->willReturn(2);
        $connection2->sourceScene = $scene;
        $connection2->sourceLabel = 'Go South';

        $targetScene2 = $this->createMock(Scene::class);
        $targetScene2->method(PropertyHook::get("id"))->willReturn(30);
        $connection2->targetScene = $targetScene2;

        // Scene action group
        $sceneActionGroup = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup->method(PropertyHook::get("title"))->willReturn('Explore');
        $sceneActionGroup->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection([$connection1]));

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup]));
        $scene->method('getConnections')->willReturn(new ArrayCollection([$connection1, $connection2]));

        $this->diceBag->method('getRandomString')->willReturn('action-id');

        $this->renderer->addDefaultActionGroups($stage);
        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->getActionGroups();
        $this->assertCount(3, $actionGroups);
        $this->assertArrayHasKey("lotgd.actionGroup.custom.13", $actionGroups);
        $this->assertCount(1, $actionGroups["lotgd.actionGroup.custom.13"]->getActions());

        $emptyGroupActions = $stage->getActionGroups()[ActionGroup::EMPTY]->getActions();
        $this->assertCount(1, $emptyGroupActions);
        $this->assertEquals('Go South', array_first($emptyGroupActions)->title);
    }

    /**
     * Test that bidirectional connections are handled correctly
     */
    public function testAddActionsWithBidirectionalConnection(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Connection where current scene is the target
        $connection = $this->createMock(SceneConnection::class);
        $connection->method(PropertyHook::get("id"))->willReturn(1);
        $connection->targetScene = $scene;
        $connection->targetLabel = 'Go Back';

        $sourceScene = $this->createMock(Scene::class);
        $sourceScene->method(PropertyHook::get("id"))->willReturn(21);
        $connection->sourceScene = $sourceScene;

        $sceneActionGroup = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup->method(PropertyHook::get("title"))->willReturn('Movement');
        $sceneActionGroup->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection([$connection]));

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup]));
        $scene->method('getConnections')->willReturn(new ArrayCollection([]));

        $this->diceBag->method('getRandomString')->willReturn('action-id');

        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->getActionGroups();
        $action = array_first(array_first($actionGroups)->getActions());
        $this->assertSame('Go Back', $action->title);
        $this->assertSame(21, $action->sceneId);
    }


    /**
     * Test that multiple action groups are handled correctly
     */
    public function testAddActionsWithMultipleActionGroups(): void
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // First action group
        $sceneActionGroup1 = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup1->method(PropertyHook::get("id"))->willReturn(13);
        $sceneActionGroup1->method(PropertyHook::get("title"))->willReturn('Explore');
        $sceneActionGroup1->method(PropertyHook::get("sorting"))->willReturn(10);
        $sceneActionGroup1->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection());

        // Second action group
        $sceneActionGroup2 = $this->createMock(SceneActionGroup::class);
        $sceneActionGroup2->method(PropertyHook::get("id"))->willReturn(14);
        $sceneActionGroup2->method(PropertyHook::get("title"))->willReturn('Combat');
        $sceneActionGroup2->method(PropertyHook::get("sorting"))->willReturn(20);
        $sceneActionGroup2->method(PropertyHook::get("connections"))->willReturn(new ArrayCollection());

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection([$sceneActionGroup1, $sceneActionGroup2]));
        $scene->method('getConnections')->willReturn(new ArrayCollection());

        $this->renderer->addActions($stage, $scene);

        $actionGroups = array_values($stage->getActionGroups());
        $this->assertCount(2, $actionGroups);
        $this->assertEquals('Explore', $actionGroups[0]->getTitle());
        $this->assertEquals('Combat', $actionGroups[1]->getTitle());
        $this->assertEquals(10, $actionGroups[0]->getWeight());
        $this->assertEquals(20, $actionGroups[1]->getWeight());
    }
}
