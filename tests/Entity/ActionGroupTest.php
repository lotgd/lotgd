<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(ActionGroup::class)]
#[UsesClass(Action::class)]
#[UsesClass(DiceBag::class)]
class ActionGroupTest extends TestCase
{
    public function testConstructorWithoutParameters(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertIsArray($actionGroup->actions);
        $this->assertEmpty($actionGroup->actions);
        $this->assertIsArray($actionGroup->actionsByReference);
        $this->assertEmpty($actionGroup->actionsByReference);
    }

    public function testConstructorWithAllParameters(): void
    {
        $action1 = new Action();
        $action2 = new Action();
        $actions = [$action1, $action2];

        $actionGroup = new ActionGroup(
            id: 'test-id',
            title: 'Test Title',
            weight: 42,
            actions: $actions
        );

        $this->assertSame('test-id', $actionGroup->getId());
        $this->assertSame('Test Title', $actionGroup->getTitle());
        $this->assertSame(42, $actionGroup->getWeight());
        $this->assertCount(2, $actionGroup->getActions());
    }

    public function testConstructorWithPartialParameters(): void
    {
        $actionGroup = new ActionGroup(
            id: 'partial-id',
            weight: 10
        );

        $this->assertSame('partial-id', $actionGroup->getId());
        $this->assertNull($actionGroup->getTitle());
        $this->assertSame(10, $actionGroup->getWeight());
        $this->assertEmpty($actionGroup->getActions());
    }

    public function testSetAndGetId(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setId('new-id');
        $this->assertSame('new-id', $actionGroup->getId());
    }

    public function testSetAndGetTitle(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setTitle('New Title');
        $this->assertSame('New Title', $actionGroup->getTitle());
    }

    public function testGetTitleReturnsNullByDefault(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertNull($actionGroup->getTitle());
    }

    public function testSetAndGetWeight(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setWeight(50);
        $this->assertSame(50, $actionGroup->getWeight());
    }

    public function testGetWeightReturnsZeroByDefault(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertSame(0, $actionGroup->getWeight());
    }

    public function testSetWeightWithNegativeValue(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setWeight(-10);
        $this->assertSame(-10, $actionGroup->getWeight());
    }

    public function testSetWeightWithLargeValue(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setWeight(PHP_INT_MAX);
        $this->assertSame(PHP_INT_MAX, $actionGroup->getWeight());
    }

    public function testAddAction(): void
    {
        $actionGroup = new ActionGroup();
        $action = new Action();
        $result = $actionGroup->addAction($action);

        $this->assertCount(1, $actionGroup->actions);
        $this->assertArrayHasKey($action->id, $actionGroup->actions);
        $this->assertSame($action, $actionGroup->actions[$action->id]);
        $this->assertSame($actionGroup, $result);
    }

    public function testAddActionWithReference(): void
    {
        $actionGroup = new ActionGroup();
        $action = new Action(reference: 'test-ref');
        $actionGroup->addAction($action);

        $this->assertCount(1, $actionGroup->actionsByReference);
        $this->assertArrayHasKey('test-ref', $actionGroup->actionsByReference);
        $this->assertSame($action->id, $actionGroup->actionsByReference['test-ref']);
    }

    public function testAddActionWithDuplicateReferenceThrowsException(): void
    {
        $actionGroup = new ActionGroup();
        $action1 = new Action(reference: 'duplicate-ref');
        $action2 = new Action(reference: 'duplicate-ref');

        $actionGroup->addAction($action1);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('An action reference must be unique within an action group. The reference duplicate-ref already exists.');
        $actionGroup->addAction($action2);
    }

    public function testAddMultipleActions(): void
    {
        $action1 = new Action();
        $action2 = new Action();
        $action3 = new Action();

        $actionGroup = new ActionGroup();
        $actionGroup->addAction($action1)->addAction($action2)->addAction($action3);

        $this->assertCount(3, $actionGroup->actions);
        $this->assertArrayHasKey($action1->id, $actionGroup->actions);
        $this->assertArrayHasKey($action2->id, $actionGroup->actions);
        $this->assertArrayHasKey($action3->id, $actionGroup->actions);
    }

    public function testGetActionByReference(): void
    {
        $actionGroup = new ActionGroup();
        $action = new Action(reference: 'find-me');
        $actionGroup->addAction($action);

        $foundAction = $actionGroup->getActionByReference('find-me');
        $this->assertSame($action, $foundAction);
    }

    public function testGetActionByReferenceReturnsNullForNonExistent(): void
    {
        $actionGroup = new ActionGroup();
        $foundAction = $actionGroup->getActionByReference('non-existent');
        $this->assertNull($foundAction);
    }

    public function testGetActionByReferenceReturnsNullWhenActionIdNotFound(): void
    {
        $actionGroup = new ActionGroup();
        // Simulate scenario where reference exists but action was removed
        $actionGroup->actionsByReference['orphaned-ref'] = 'non-existent-id';
        
        $foundAction = $actionGroup->getActionByReference('orphaned-ref');
        $this->assertNull($foundAction);
    }

    public function testGetActionById(): void
    {
        $actionGroup = new ActionGroup();
        $action = new Action();
        $actionGroup->addAction($action);

        $foundAction = $actionGroup->getActionById($action->id);
        $this->assertSame($action, $foundAction);
    }

    public function testGetActionByIdReturnsNullForNonExistent(): void
    {
        $actionGroup = new ActionGroup();
        $foundAction = $actionGroup->getActionById('non-existent-id');
        $this->assertNull($foundAction);
    }

    public function testSetActions(): void
    {
        $action1 = new Action();
        $action2 = new Action();
        $actions = [$action1, $action2];

        $actionGroup = new ActionGroup();
        $result = $actionGroup->setActions($actions);

        $this->assertCount(2, $actionGroup->getActions());
        $this->assertArrayHasKey($action1->id, $actionGroup->actions);
        $this->assertArrayHasKey($action2->id, $actionGroup->actions);
        $this->assertSame($actionGroup, $result);
    }

    public function testSetActionsWithReferences(): void
    {
        $action1 = new Action(reference: 'ref1');
        $action2 = new Action(reference: 'ref2');
        $actions = [$action1, $action2];

        $actionGroup = new ActionGroup();
        $actionGroup->setActions($actions);

        $this->assertCount(2, $actionGroup->actionsByReference);
        $this->assertSame($action1, $actionGroup->getActionByReference('ref1'));
        $this->assertSame($action2, $actionGroup->getActionByReference('ref2'));
    }

    public function testSetActionsClearsExistingActions(): void
    {
        $actionGroup = new ActionGroup();
        $oldAction = new Action(reference: 'old-ref');
        $actionGroup->addAction($oldAction);

        $newAction = new Action(reference: 'new-ref');
        $actionGroup->setActions([$newAction]);

        $this->assertCount(1, $actionGroup->actions);
        $this->assertCount(1, $actionGroup->actionsByReference);
        $this->assertNull($actionGroup->getActionByReference('old-ref'));
        $this->assertSame($newAction, $actionGroup->getActionByReference('new-ref'));
    }

    public function testSetActionsWithEmptyArray(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setActions([]);
        $this->assertEmpty($actionGroup->getActions());
        $this->assertEmpty($actionGroup->actionsByReference);
    }

    public function testSetActionsThrowsTypeErrorOnInvalidElement(): void
    {
        $actionGroup = new ActionGroup();
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("All array elements must be an instance of " . Action::class);

        $actionGroup->setActions([new Action(), 'invalid']);
    }

    public function testSetActionsThrowsTypeErrorOnNonActionObject(): void
    {
        $this->expectException(TypeError::class);
        $actionGroup = new ActionGroup();
        $actionGroup->setActions([new \stdClass()]);
    }

    public function testGetActions(): void
    {
        $actionGroup = new ActionGroup();
        $action1 = new Action();
        $action2 = new Action();
        $actions = [$action1, $action2];

        $actionGroup->setActions($actions);
        $retrieved = array_values($actionGroup->getActions());

        $this->assertSame($actions, $retrieved);
        $this->assertCount(2, $retrieved);
    }

    public function testGetActionsReturnsEmptyArrayByDefault(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertIsArray($actionGroup->getActions());
        $this->assertEmpty($actionGroup->getActions());
    }

    public function testConstantHidden(): void
    {
        $this->assertSame('lotgd.actionGroup.hidden', ActionGroup::HIDDEN);
    }

    public function testConstantEmpty(): void
    {
        $this->assertSame('lotgd.actionGroup.empty', ActionGroup::EMPTY);
    }

    public function testMethodChaining(): void
    {
        $actionGroup = new ActionGroup();
        $action = new Action();
        $result = $actionGroup
            ->setId('chained-id')
            ->setTitle('Chained Title')
            ->setWeight(25)
            ->addAction($action);

        $this->assertSame($actionGroup, $result);
        $this->assertSame('chained-id', $actionGroup->getId());
        $this->assertSame('Chained Title', $actionGroup->getTitle());
        $this->assertSame(25, $actionGroup->getWeight());
        $this->assertCount(1, $actionGroup->getActions());
    }

    public function testReplaceActionsAfterAddingActions(): void
    {
        $actionGroup = new ActionGroup();
        $action1 = new Action();
        $action2 = new Action();
        $action3 = new Action();

        $actionGroup->addAction($action1);
        $this->assertCount(1, $actionGroup->actions);

        $actionGroup->setActions([$action2, $action3]);
        $this->assertCount(2, $actionGroup->actions);

        $actions = array_values($actionGroup->actions);
        $this->assertSame($action2, $actions[0]);
        $this->assertSame($action3, $actions[1]);
    }

    public function testActionsPropertyIsPublic(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertTrue(property_exists($actionGroup, 'actions'));
        $reflection = new \ReflectionProperty(ActionGroup::class, 'actions');
        $this->assertTrue($reflection->isPublic());
    }
}