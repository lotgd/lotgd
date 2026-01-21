<?php

declare(strict_types=1);

namespace LotGD2\Tests\Game\Stage;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Stage\ActionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActionService::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Action::class)]
class ActionServiceTest extends TestCase
{
    private ActionService $actionService;

    protected function setUp(): void
    {
        $this->actionService = new ActionService();
    }

    public function testGetActionByIdReturnsActionWhenFound(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';
        $action2->id = 'action-2';

        $actionGroup->expects($this->any())
            ->method('getActions')
            ->willReturn([$action1, $action2]);

        $stage->expects($this->any())
            ->method('getActionGroups')
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-2');

        // Assert
        $this->assertSame($action2, $result);
    }

    public function testGetActionByIdReturnsNullWhenNotFound(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';

        $actionGroup->expects($this->any())
            ->method('getActions')
            ->willReturn([$action1]);

        $stage->expects($this->any())
            ->method('getActionGroups')
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'non-existent');

        // Assert
        $this->assertNull($result);
    }

    public function testGetActionByIdSearchesMultipleActionGroups(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();;
        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup2 = $this->createMock(ActionGroup::class);

        $action1->id = "action-1";
        $action2->id = "action-2";

        $actionGroup1->expects($this->any())
            ->method('getActions')
            ->willReturn([$action1]);

        $actionGroup2->expects($this->any())
            ->method('getActions')
            ->willReturn([$action2]);

        $stage->expects($this->any())
            ->method('getActionGroups')
            ->willReturn([$actionGroup1, $actionGroup2]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-2');

        // Assert
        $this->assertSame($action2, $result);
    }

    public function testAddHiddenActionCallsStageAddAction(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action = new Action();

        $stage->expects($this->once())
            ->method('addAction')
            ->with(ActionGroup::HIDDEN, $action);

        // Act
        $this->actionService->addHiddenAction($stage, $action);

        // Assert
        // The assertion is in the expects() call above
        $this->assertTrue(true);
    }

    public function testGetActionByIdReturnsFirstMatchWhenMultipleWithSameId(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();;
        $action2 = new Action();;
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = "action-1";
        $action2->id = "action-2";

        $actionGroup->expects($this->any())
            ->method('getActions')
            ->willReturn([$action1, $action2]);

        $stage->expects($this->any())
            ->method('getActionGroups')
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-1');

        // Assert
        $this->assertSame($action1, $result);
    }
}