<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Character\DragonCounter;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Random\DiceBagInterface;
use PhpParser\Builder\Property;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[CoversClass(DragonCounter::class)]
#[UsesClass(Action::class)]
#[AllowMockObjectsWithoutExpectations]
class DragonCounterTest extends TestCase
{
    private DragonCounter $dragonCounter;
    private LoggerInterface&MockObject $logger;
    private DiceBagInterface&MockObject $diceBag;
    private Stopwatch&MockObject $stopwatch;
    private Character&MockObject $character;
    private Stats&MockObject $stats;
    private Health&MockObject $health;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->diceBag = $this->createMock(DiceBagInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->character = $this->createMock(Character::class);
        $this->health = $this->createMock(Health::class);
        $this->stats = $this->createMock(Stats::class);

        $this->dragonCounter = new DragonCounter(
            $this->logger,
            $this->diceBag,
            $this->stopwatch,
            $this->character,
            $this->health,
            $this->stats,
        );
    }

    public function testDragonCounterGetterReturnsCorrectValue(): void
    {
        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::CounterPropertyName, 0)
            ->willReturn(5);

        $this->assertEquals(5, $this->dragonCounter->dragonCounter);
    }

    public function testDragonCounterGetterReturnsDefaultValue(): void
    {
        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::CounterPropertyName, 0)
            ->willReturn(0);

        $this->assertEquals(0, $this->dragonCounter->dragonCounter);
    }

    public function testDragonCounterSetterStoresValue(): void
    {
        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(123);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('123: Set dragon counter value to 7.');

        $this->character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::CounterPropertyName, 7);

        $this->dragonCounter->dragonCounter = 7;
    }

    public function testChoicesGetterReturnsCorrectValue(): void
    {
        $expectedChoices = [
            ['choice' => 'health', 'age' => 1],
            ['choice' => 'strength', 'age' => 2]
        ];

        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::ChoicePropertyName, [])
            ->willReturn($expectedChoices);

        $this->assertEquals($expectedChoices, $this->dragonCounter->choices);
    }

    public function testChoicesGetterReturnsDefaultEmptyArray(): void
    {
        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::ChoicePropertyName, [])
            ->willReturn([]);

        $this->assertEquals([], $this->dragonCounter->choices);
    }

    public function testChoicesSetterStoresValue(): void
    {
        $choices = [['choice' => 'defense']];

        $this->character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::ChoicePropertyName, $choices);

        $this->dragonCounter->choices = $choices;
    }

    public function testAddChoiceWithoutKwargs(): void
    {
        $existingChoices = [['choice' => 'health']];
        $expectedChoices = [
            ['choice' => 'health'],
            ['choice' => 'strength']
        ];

        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(456);

        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::ChoicePropertyName, [])
            ->willReturn($existingChoices);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('456: Add DragonCounter choice strength.', []);

        $this->character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::ChoicePropertyName, $expectedChoices);

        $result = $this->dragonCounter->addChoice('strength');

        $this->assertSame($this->dragonCounter, $result);
    }

    public function testAddChoiceWithKwargs(): void
    {
        $existingChoices = [];
        $kwargs = ['age' => 5, 'bonus' => 'extra'];
        $expectedChoices = [
            ['choice' => 'defense', 'age' => 5, 'bonus' => 'extra']
        ];

        $this->character->expects($this->any())
            ->method(PropertyHook::get("id"))
            ->willReturn(789);

        $this->character->expects($this->once())
            ->method('getProperty')
            ->with(DragonCounter::ChoicePropertyName, [])
            ->willReturn($existingChoices);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('789: Add DragonCounter choice defense.', $kwargs);

        $this->character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::ChoicePropertyName, $expectedChoices);

        $result = $this->dragonCounter->addChoice('defense', $kwargs);

        $this->assertSame($this->dragonCounter, $result);
    }

    public function testOnNewDayEventWithHealthChoice(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = ['dk' => 'health'];

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->health->expects($this->once())
            ->method("addMaxHealth")
            ->with(5);

        $this->stats->expects($this->never())
            ->method("setAttack");

        $this->stats->expects($this->never())
            ->method("setDefense");

        // Expected to have property set
        $character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::ChoicePropertyName, [['choice' => 'health']]);

        // Mock character properties for the DragonCounter instance
        // Length of ChoiceProperty shoud match CounterProperty to have 0 points left.
        $character->expects($this->atLeastOnce())
            ->method('getProperty')
            ->willReturnMap([
                [DragonCounter::CounterPropertyName, 0, 0]
            ]);

        // Mock for adding health
        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(999);

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testOnNewDayEventWithStrengthChoice(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = ['dk' => 'strength'];

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        // Mock character properties
        $character->expects($this->once())
            ->method('setProperty')
            ->willReturnMap([
                [DragonCounter::ChoicePropertyName, [['choice' => 'strength']], $character],
            ]);

        $this->stats->expects($this->exactly(1))
            ->method("getAttack")
            ->willReturn(10);

        $this->stats->expects($this->exactly(1))
            ->method("setAttack")
            ->with(11);

        $this->stats->expects($this->never())
            ->method("setDefense");

        $this->health->expects($this->never())
            ->method("addMaxHealth");

        // Mock for stats modification
        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(999);

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testOnNewDayEventWithDefenseChoice(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = ['dk' => 'defense'];

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        // Mock character properties
        $character->expects($this->once())
            ->method('setProperty')
            ->with(DragonCounter::ChoicePropertyName, [['choice' => 'defense']]);

        // Mock for stats modification
        $this->stats->expects($this->exactly(1))
            ->method("getDefense")
            ->willReturn(10);

        $this->stats->expects($this->exactly(1))
            ->method("setDefense")
            ->with(11);

        $this->stats->expects($this->never())
            ->method("setAttack");

        $this->health->expects($this->never())
            ->method("addMaxHealth");

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(999);

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testOnNewDayEventWithInvalidChoice(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = ['dk' => 'invalid'];

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("stage"))
            ->willReturn($stage);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        // Mock character properties
        $character->expects($this->exactly(2))
            ->method('getProperty')
            ->willReturnMap([
                [DragonCounter::ChoicePropertyName, [], []],
                [DragonCounter::CounterPropertyName, 0, 1]
            ]);

        // Should NOT set property for invalid choice
        $character->expects($this->never())
            ->method('setProperty');

        // Mock stage interactions - should still show dragon points screen
        $stage->expects($this->once())
            ->method(PropertyHook::set("title"))
            ->with('Dragon points');

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with($this->stringContains('You earn one dragon point each time you slay the dragon'));

        $stage->expects($this->once())
            ->method('addContext')
            ->with('dragonPointsLeft', 1); // No choice was made, so full dragon point remains

        $event->expects($this->exactly(3))
            ->method('addAction')
            ->with(ActionGroup::EMPTY, $this->isInstanceOf(Action::class));

        $event->expects($this->once())
            ->method('setStopRender');

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testOnNewDayEventWithoutDragonPointParameter(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = []; // No 'dk' parameter

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("stage"))
            ->willReturn($stage);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        // Mock character properties
        $character->expects($this->exactly(2))
            ->method('getProperty')
            ->willReturnMap([
                [DragonCounter::ChoicePropertyName, [], []],
                [DragonCounter::CounterPropertyName, 0, 1]
            ]);

        // Should NOT set any properties since no choice was made
        $character->expects($this->never())
            ->method('setProperty');

        // Mock stage interactions
        $stage->expects($this->once())
            ->method(PropertyHook::set("title"))
            ->with('Dragon points');

        $stage->expects($this->once())
            ->method('addContext')
            ->with('dragonPointsLeft', 1);

        $event->expects($this->exactly(3))
            ->method('addAction');

        $event->expects($this->once())
            ->method('setStopRender');

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testOnNewDayEventWithNoDragonPointsLeft(): void
    {
        $event = $this->createMock(StageChangeEvent::class);
        $action = $this->createMock(Action::class);
        $character = $this->character;

        $action->parameters = [];

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("character"))
            ->willReturn($character);

        $event->expects($this->atLeastOnce())
            ->method(PropertyHook::get("action"))
            ->willReturn($action);

        $this->stopwatch->expects($this->exactly(1))
            ->method('start')
            ->with('lotgd2.DragonCounter.onNewDay');

        $this->stopwatch->expects($this->exactly(1))
            ->method('stop')
            ->with('lotgd2.DragonCounter.onNewDay');

        // Mock character properties - dragon counter matches choices count
        $character->expects($this->exactly(2))
            ->method('getProperty')
            ->willReturnMap([
                [DragonCounter::ChoicePropertyName, [], [['choice' => 'health']]],
                [DragonCounter::CounterPropertyName, 0, 1]
            ]);

        // Should NOT interact with stage since dragon points left < 0
        $event->expects($this->never())
            ->method('addAction');

        $event->expects($this->never())
            ->method('setStopRender');

        $this->dragonCounter->onNewDayEvent($event);
    }

    public function testConstants(): void
    {
        $this->assertEquals("dragonCounter", DragonCounter::CounterPropertyName);
        $this->assertEquals("dragonCounterChoice", DragonCounter::ChoicePropertyName);
    }
}