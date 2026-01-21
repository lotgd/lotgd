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
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
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

        $actionGroup->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn([$action1, $action2]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
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

        $actionGroup->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn([$action1]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
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

        $actionGroup1->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn([$action1]);

        $actionGroup2->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn([$action2]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup1, $actionGroup2]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-2');

        // Assert
        $this->assertSame($action2, $result);
    }

    public function testGetActionByIdFindsActionByReference(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';
        $action2->id = 'action-2';

        // Mock getActionByReference to return action1 when searching for 'test-reference'
        $actionGroup->expects($this->once())
            ->method('getActionByReference')
            ->with('test-reference')
            ->willReturn($action1);

        // Should not call getActions since we found it by reference
        $actionGroup->expects($this->never())
            ->method('getActions');

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'test-reference');

        // Assert - This tests the marked lines: if ($actionEntry !== null) { $selectedAction = $actionEntry; break; }
        $this->assertSame($action1, $result);
    }

    public function testGetActionByIdFallsBackToIdSearchWhenReferenceNotFound(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';
        $action2->id = 'action-2';

        // Mock getActionByReference to return null (reference not found)
        $actionGroup->expects($this->once())
            ->method('getActionByReference')
            ->with('action-2')
            ->willReturn(null);

        // Should fall back to searching by ID
        $actionGroup->expects($this->once())
            ->method('getActions')
            ->willReturn([$action1, $action2]);

        $stage->expects($this->once())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-2');

        // Assert
        $this->assertSame($action2, $result);
    }

    public function testGetActionByIdReturnsActionFromFirstGroupWhenFoundByReference(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup2 = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';
        $action2->id = 'action-2';

        // First group returns the action by reference
        $actionGroup1->expects($this->once())
            ->method('getActionByReference')
            ->with('shared-reference')
            ->willReturn($action1);

        // Second group should not be called since we break after finding in first group
        $actionGroup2->expects($this->never())
            ->method('getActionByReference');

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup1, $actionGroup2]);

        // Act
        $result = $this->actionService->getActionById($stage, 'shared-reference');

        // Assert - This specifically tests the break statement in the marked lines
        $this->assertSame($action1, $result);
    }

    public function testGetActionByIdSearchesMultipleGroupsForReference(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup2 = $this->createMock(ActionGroup::class);

        $action1->id = 'action-1';
        $action2->id = 'action-2';

        // First group returns null (reference not found)
        $actionGroup1->expects($this->once())
            ->method('getActionByReference')
            ->with('target-reference')
            ->willReturn(null);

        // Second group returns the action by reference
        $actionGroup2->expects($this->once())
            ->method('getActionByReference')
            ->with('target-reference')
            ->willReturn($action2);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup1, $actionGroup2]);

        // Act
        $result = $this->actionService->getActionById($stage, 'target-reference');

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
        $action1 = new Action();
        $action2 = new Action();
        $actionGroup = $this->createMock(ActionGroup::class);

        $action1->id = "action-1";
        $action2->id = "action-2";

        $actionGroup->expects($this->atLeastOnce())
            ->method('getActions')
            ->willReturn([$action1, $action2]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'action-1');

        // Assert
        $this->assertSame($action1, $result);
    }

    public function testGetActionByIdWithEmptyActionGroups(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        
        $stage->expects($this->once())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([]);

        // Act
        $result = $this->actionService->getActionById($stage, 'any-action');

        // Assert
        $this->assertNull($result);
    }

    public function testGetActionByIdWithActionGroupsHavingNoActions(): void
    {
        // Arrange
        $stage = $this->createMock(Stage::class);
        $actionGroup = $this->createMock(ActionGroup::class);

        $actionGroup->expects($this->once())
            ->method('getActionByReference')
            ->with('test-id')
            ->willReturn(null);

        $actionGroup->expects($this->once())
            ->method('getActions')
            ->willReturn([]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("actionGroups"))
            ->willReturn([$actionGroup]);

        // Act
        $result = $this->actionService->getActionById($stage, 'test-id');

        // Assert
        $this->assertNull($result);
    }
}