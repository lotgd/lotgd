<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
        $this->assertSame($actions, $actionGroup->getActions());
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
        $this->assertSame($action, $actionGroup->actions[0]);
        $this->assertSame($actionGroup, $result);
    }

    public function testAddMultipleActions(): void
    {
        $action1 = new Action();
        $action2 = new Action();
        $action3 = new Action();

        $actionGroup = new ActionGroup();
        $actionGroup->addAction($action1)->addAction($action2)->addAction($action3);

        $this->assertCount(3, $actionGroup->actions);
        $this->assertSame($action1, $actionGroup->actions[0]);
        $this->assertSame($action2, $actionGroup->actions[1]);
        $this->assertSame($action3, $actionGroup->actions[2]);
    }

    public function testSetActions(): void
    {
        $action1 = new Action();
        $action2 = new Action();
        $actions = [$action1, $action2];

        $actionGroup = new ActionGroup();
        $result = $actionGroup->setActions($actions);

        $this->assertSame($actions, $actionGroup->getActions());
        $this->assertSame($actionGroup, $result);
    }

    public function testSetActionsWithEmptyArray(): void
    {
        $actionGroup = new ActionGroup();
        $actionGroup->setActions([]);
        $this->assertEmpty($actionGroup->getActions());
    }

    public function testSetActionsThrowsTypeErrorOnInvalidElement(): void
    {
        $actionGroup = new ActionGroup();
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("All array elements must be an instance of " . Action::class);

        $actionGroup->setActions([new Action(), 'invalid']);
    }

    public function testSetActionsThrowsTypeErrorOnNonActionObject(): void
    {
        $this->expectException(\TypeError::class);
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
        $retrieved = $actionGroup->getActions();

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
        $this->assertSame($action2, $actionGroup->actions[0]);
        $this->assertSame($action3, $actionGroup->actions[1]);
    }

    public function testActionsPropertyIsPublic(): void
    {
        $actionGroup = new ActionGroup();
        $this->assertTrue(property_exists($actionGroup, 'actions'));
        $reflection = new \ReflectionProperty(ActionGroup::class, 'actions');
        $this->assertTrue($reflection->isPublic());
    }
}