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
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SceneRenderer::class)]
#[UsesClass(SceneRepository::class)]
#[UsesClass(Scene::class)]
#[UsesClass(Character::class)]
#[UsesClass(Stage::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(SceneConnection::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Action::class)]
#[UsesClass(Equipment::class)]
#[UsesClass(Gold::class)]
#[UsesClass(Health::class)]
#[UsesClass(Stats::class)]
#[UsesClass(ExpressionService::class)]
class SceneRendererTest extends TestCase
{
    private SceneRenderer $renderer;
    private SceneRepository&MockObject $sceneRepository;
    private ActionService $actionService;
    private LoggerInterface $logger;
    private DiceBag&MockObject $diceBag;

    protected function setUp(): void
    {
        $this->sceneRepository = $this->createMock(SceneRepository::class);
        $this->diceBag = $this->createMock(DiceBag::class);
        $this->actionService = $this->createStub(ActionService::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->renderer = new SceneRenderer(
            $this->sceneRepository,
            $this->diceBag,
            $this->actionService,
            $this->logger,
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
        $actionService = $this->createStub(ActionService::class);

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag, $actionService, $this->logger);

        $stage = $sceneRenderer->renderDefault($character);

        $this->assertSame($scene, $stage->scene);
    }

    /**
     * Test if the stage is set up correctly
     */
    public function testIfStageIsSetCorrectly(): void
    {
        $scene = $this->getSceneMock("Test Scene", "A nice scenery");

        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createMock(DiceBag::class);
        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())->method("resetActionGroups");

        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag, $actionService, $this->logger);
        $stage = $sceneRenderer->render($stage, $scene);

        $this->assertNotNull($stage);
        $this->assertSame("Test Scene", $stage->title);
        $this->assertSame("A nice scenery", $stage->description);

        $actionGroups = $stage->actionGroups;
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
        $sceneConnection->sourceExpression = null;
        $sceneConnection->targetExpression = null;

        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = new DiceBag();
        $actionService = $this->createStub(ActionService::class);

        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->once())->method("evaluate")->willReturn(true);

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag, $actionService, $this->logger);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection, $expressionService);

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
        $actionService = $this->createStub(ActionService::class);

        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->once())->method("evaluate")->willReturn(true);

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag, $actionService, $this->logger);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection, $expressionService);

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
        $actionService = $this->createStub(ActionService::class);
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->exactly(0))->method("evaluate")->willReturn(true);

        $sceneRenderer = new SceneRenderer($sceneRepository, $diceBag, $actionService, $this->logger);

        $action = $sceneRenderer->createActionFromConnection($scene, $sceneConnection, $expressionService);

        $this->assertStringStartsWith("#invalidConnection", $action->title);
        $this->assertNull($action->sceneId);
    }

    /**
     * Test that addActions handles empty action groups correctly
     */
    public function testAddActionsWithEmptyScene(): void
    {
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
        $scene = $this->createMock(Scene::class);

        $scene->method(PropertyHook::get("actionGroups"))->willReturn(new ArrayCollection());
        $scene->method('getConnections')->willReturn(new ArrayCollection());

        $this->renderer->addActions($stage, $scene);

        $actionGroups = $stage->actionGroups;
        $this->assertCount(0, $actionGroups);;
    }

    /**
     * Test that addActions creates action groups from scene action groups
     */
    public function testAddActionsCreatesActionGroupsFromSceneActionGroups(): void
    {
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
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

        $actionGroups = $stage->actionGroups;
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
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
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

        $actionGroups = $stage->actionGroups;
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
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
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
        $actionGroups = $stage->actionGroups;
        $this->assertCount(1, $actionGroups);
        $this->assertArrayHasKey("lotgd.actionGroup.custom.13", $actionGroups);
        $this->assertCount(1, $actionGroups["lotgd.actionGroup.custom.13"]->getActions());
    }

    /**
     * Test that visible-only connections not in action groups are added to empty group
     */
    public function testAddActionsVisibleConnectionsAddedToEmptyGroup(): void
    {
        $stage = $this->createMock(Stage::class);
        $stage->expects($this->atLeastOnce())->method(PropertyHook::get("owner"))->willReturn($this->createStub(Character::class));
        $actionGroup = $this->createMocK(ActionGroup::class);
        $stage->expects($this->once())->method("addAction")->willReturnCallback(
            function (string $group, Action $action) use ($stage) {
                $this->assertSame(ActionGroup::EMPTY, $group);
                $this->assertSame('Go South', $action->title);
                $this->assertSame(3, $action->sceneId);
                return $stage;
            }
        );

        $stage->method(PropertyHook::get("actionGroups"))->willReturn([
            ActionGroup::EMPTY => $actionGroup,
        ]);
        $stage->expects($this->never())->method("addActionGroup");

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

        $this->renderer->addActions($stage, $scene);
    }

    /**
     * Test that connections from both action groups and visible connections are handled correctly
     */
    public function testAddActionsMixedActionGroupsAndVisibleConnections(): void
    {
        $stage = $this->createMock(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($this->createStub(Character::class));
        $stage->expects($this->once())->method("addActionGroup")->willReturnCallback(
            function (ActionGroup $actionGroup) use ($stage){
                $this->assertSame("lotgd.actionGroup.custom.13", $actionGroup->id);

                $actions = array_values($actionGroup->actions);
                $this->assertCount(1, $actions);
                $this->assertSame("Go North", $actions[0]->title);
                $this->assertSame(20, $actions[0]->sceneId);

                return $stage;
            }
        );

        $stage->expects($this->once())->method("addAction")->willReturnCallback(
            function (string $group, Action $action) use ($stage) {
                $this->assertSame(ActionGroup::EMPTY, $group);
                $this->assertSame('Go South', $action->title);
                $this->assertSame(30, $action->sceneId);
                return $stage;
            }
        );

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

        $this->renderer->addActions($stage, $scene);
    }

    /**
     * Test that bidirectional connections are handled correctly
     */
    public function testAddActionsWithBidirectionalConnection(): void
    {
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
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

        $actionGroups = $stage->actionGroups;
        $action = array_first(array_first($actionGroups)->getActions());
        $this->assertSame('Go Back', $action->title);
        $this->assertSame(21, $action->sceneId);
    }


    /**
     * Test that multiple action groups are handled correctly
     */
    public function testAddActionsWithMultipleActionGroups(): void
    {
        $stage = new Stage(
            owner: $this->createStub(Character::class),
        );
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

        $actionGroups = array_values($stage->actionGroups);
        $this->assertCount(2, $actionGroups);
        $this->assertEquals('Explore', $actionGroups[0]->getTitle());
        $this->assertEquals('Combat', $actionGroups[1]->getTitle());
        $this->assertEquals(10, $actionGroups[0]->getWeight());
        $this->assertEquals(20, $actionGroups[1]->getWeight());
    }
}
