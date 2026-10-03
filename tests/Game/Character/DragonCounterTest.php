<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Stage\ActionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[CoversClass(DragonCounterHandler::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(Character::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Stage::class)]
#[UsesClass(StageChangeEvent::class)]
class DragonCounterTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>>|null $choices
     */
    private function createCharacter(
        ?string $name = "Hero",
        ?int $id = 123,
        ?int $dragonCounter = null,
        ?array $choices = null,
    ): Character {
        $character = new Character(name: $name);
        if ($id !== null) {
            $reflection = new \ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }
        if ($dragonCounter !== null) {
            $character->setProperty(DragonCounterHandler::CounterPropertyName, $dragonCounter);
        }
        if ($choices !== null) {
            $character->setProperty(DragonCounterHandler::ChoicePropertyName, $choices);
        }

        return $character;
    }

    private function createDragonCounterHandler(
        ?LoggerInterface $logger = null,
        ?DiceBagInterface $diceBag = null,
        ?Stopwatch $stopwatch = null,
        ?Character $character = null,
        ?HealthHandler $health = null,
        ?StatsHandler $stats = null,
        ?ActionService $actionService = null,
    ): DragonCounterHandler {
        return new DragonCounterHandler(
            $logger ?? $this->createStub(LoggerInterface::class),
            $diceBag ?? $this->createStub(DiceBagInterface::class),
            $stopwatch ?? $this->createStub(Stopwatch::class),
            $character ?? $this->createCharacter(),
            $health ?? $this->createStub(HealthHandler::class),
            $stats ?? $this->createStub(StatsHandler::class),
            $actionService ?? $this->createStub(ActionService::class),
        );
    }

    public function testConstants(): void
    {
        $this->assertSame("dragonCounter", DragonCounterHandler::CounterPropertyName);
        $this->assertSame("dragonCounterChoice", DragonCounterHandler::ChoicePropertyName);
    }

    public function testGetDragonCounterReturnsZeroByDefault(): void
    {
        $character = $this->createCharacter();
        $handler = $this->createDragonCounterHandler();

        $this->assertSame(0, $handler->getDragonCounter($character));
    }

    public function testGetDragonCounterReturnsZeroWhenPropertyIsNull(): void
    {
        $character = $this->createCharacter();
        $character->setProperty(DragonCounterHandler::CounterPropertyName, null);
        $handler = $this->createDragonCounterHandler();

        $this->assertSame(0, $handler->getDragonCounter($character));
    }

    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([5])]
    #[TestWith([10])]
    public function testGetDragonCounterReturnsStoredValue(int $counterValue): void
    {
        $character = $this->createCharacter(dragonCounter: $counterValue);
        $handler = $this->createDragonCounterHandler();

        $this->assertSame($counterValue, $handler->getDragonCounter($character));
    }

    public function testSetDragonCounterLogsAndSetsProperty(): void
    {
        $character = $this->createCharacter(id: 42);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("42: Set dragon counter value to 7.");

        $handler = $this->createDragonCounterHandler(logger: $logger);
        $handler->setDragonCounter($character, 7);

        $this->assertSame(7, $character->getProperty(DragonCounterHandler::CounterPropertyName));
        $this->assertSame(7, $handler->getDragonCounter($character));
    }

    #[TestWith([null, 1])]
    #[TestWith([0, 1])]
    #[TestWith([3, 4])]
    #[TestWith([10, 11])]
    public function testIncrementDragonCounterLogsAndIncrementsValue(?int $initialCounter, int $expectedCounter): void
    {
        $character = $this->createCharacter(id: 99, dragonCounter: $initialCounter);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("99: Increment dragon counter by 1.");

        $handler = $this->createDragonCounterHandler(logger: $logger);
        $handler->incrementDragonCounter($character);

        $this->assertSame($expectedCounter, $character->getProperty(DragonCounterHandler::CounterPropertyName));
        $this->assertSame($expectedCounter, $handler->getDragonCounter($character));
    }

    public function testDragonCounterPropertyGetterReturnsStoredValue(): void
    {
        $character = $this->createCharacter(dragonCounter: 5);
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame(5, $handler->dragonCounter);
    }

    public function testDragonCounterPropertyGetterReturnsZeroByDefault(): void
    {
        $character = $this->createCharacter();
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame(0, $handler->dragonCounter);
    }

    public function testDragonCounterPropertyGetterReturnsZeroWhenNull(): void
    {
        $character = $this->createCharacter();
        $character->setProperty(DragonCounterHandler::CounterPropertyName, null);
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame(0, $handler->dragonCounter);
    }

    public function testDragonCounterPropertySetterStoresValueAndLogs(): void
    {
        $character = $this->createCharacter(id: 123);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("123: Set dragon counter value to 7.");

        $handler = $this->createDragonCounterHandler(logger: $logger, character: $character);
        $handler->dragonCounter = 7;

        $this->assertSame(7, $character->getProperty(DragonCounterHandler::CounterPropertyName));
        $this->assertSame(7, $handler->dragonCounter);
    }

    public function testChoicesGetterReturnsStoredChoices(): void
    {
        $choices = [
            ["choice" => "health", "age" => 1],
            ["choice" => "strength", "age" => 2],
        ];
        $character = $this->createCharacter(choices: $choices);
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame($choices, $handler->choices);
    }

    public function testChoicesGetterReturnsEmptyArrayByDefault(): void
    {
        $character = $this->createCharacter();
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame([], $handler->choices);
    }

    public function testChoicesGetterReturnsEmptyArrayWhenNull(): void
    {
        $character = $this->createCharacter();
        $character->setProperty(DragonCounterHandler::ChoicePropertyName, null);
        $handler = $this->createDragonCounterHandler(character: $character);

        $this->assertSame([], $handler->choices);
    }

    public function testChoicesSetterStoresValue(): void
    {
        $choices = [["choice" => "defense"]];
        $character = $this->createCharacter();
        $handler = $this->createDragonCounterHandler(character: $character);

        $handler->choices = $choices;

        $this->assertSame($choices, $character->getProperty(DragonCounterHandler::ChoicePropertyName));
        $this->assertSame($choices, $handler->choices);
    }

    public function testAddChoiceWithoutKwargs(): void
    {
        $character = $this->createCharacter(id: 456, choices: [["choice" => "health"]]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("456: Add DragonCounter choice strength.", []);

        $handler = $this->createDragonCounterHandler(logger: $logger, character: $character);
        $result = $handler->addChoice("strength");

        $this->assertSame($handler, $result);
        $this->assertSame([
            ["choice" => "health"],
            ["choice" => "strength"],
        ], $character->getProperty(DragonCounterHandler::ChoicePropertyName));
    }

    public function testAddChoiceWithKwargs(): void
    {
        $character = $this->createCharacter(id: 789);
        $kwargs = ["age" => 5, "bonus" => "extra"];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("789: Add DragonCounter choice defense.", $kwargs);

        $handler = $this->createDragonCounterHandler(logger: $logger, character: $character);
        $result = $handler->addChoice("defense", $kwargs);

        $this->assertSame($handler, $result);
        $this->assertSame([
            ["choice" => "defense", "age" => 5, "bonus" => "extra"],
        ], $character->getProperty(DragonCounterHandler::ChoicePropertyName));
    }

    public function testOnNewDayEventWithHealthChoiceAndRemainingPoints(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 2);
        $stage = new Stage(owner: $character);
        $action = new Action(parameters: ["dk" => "health"]);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);
        $event->method(PropertyHook::get("stage"))->willReturn($stage);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())->method("addMaxHealth")->with(5, $character);

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->never())->method("setAttack");
        $stats->expects($this->never())->method("setDefense");

        $diceBag = $this->createStub(DiceBagInterface::class);

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())->method("resetActionGroups")->with($stage);

        $event->expects($this->exactly(3))
            ->method("addAction")
            ->with(ActionGroup::EMPTY, $this->isInstanceOf(Action::class))
            ->willReturnSelf();

        $event->expects($this->once())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            diceBag: $diceBag,
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);

        $this->assertSame([["choice" => "health"]], $character->getProperty(DragonCounterHandler::ChoicePropertyName));
        $this->assertSame("Dragon points", $stage->title);
        $this->assertArrayHasKey("lotgd.paragraph.DragonCounter.dragonPointsLeft", $stage->paragraphs);

        $paragraph = $stage->paragraphs["lotgd.paragraph.DragonCounter.dragonPointsLeft"];
        $this->assertInstanceOf(Paragraph::class, $paragraph);
        $this->assertSame("lotgd.paragraph.DragonCounter.dragonPointsLeft", $paragraph->id);
        $this->assertSame(1, $paragraph->context["dragonPointsLeft"]);
        $this->assertStringContainsString("You earn one dragon point each time you slay the dragon", $paragraph->text);
    }

    public function testOnNewDayEventWithStrengthChoiceAndNoRemainingPoints(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 1);
        $stage = new Stage(owner: $character);
        $action = new Action(parameters: ["dk" => "strength"]);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->never())->method("addMaxHealth");

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())->method("getAttack")->willReturn(10);
        $stats->expects($this->once())->method("setAttack")->with(11, $character);
        $stats->expects($this->never())->method("setDefense");

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->never())->method("resetActionGroups");

        $event->expects($this->never())->method("addAction");
        $event->expects($this->never())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);

        $this->assertSame([["choice" => "strength"]], $character->getProperty(DragonCounterHandler::ChoicePropertyName));
    }

    public function testOnNewDayEventWithDefenseChoiceAndNoRemainingPoints(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 1);
        $stage = new Stage(owner: $character);
        $action = new Action(parameters: ["dk" => "defense"]);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->never())->method("addMaxHealth");

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())->method("getDefense")->willReturn(10);
        $stats->expects($this->once())->method("setDefense")->with(11, $character);
        $stats->expects($this->never())->method("setAttack");

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->never())->method("resetActionGroups");

        $event->expects($this->never())->method("addAction");
        $event->expects($this->never())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);

        $this->assertSame([["choice" => "defense"]], $character->getProperty(DragonCounterHandler::ChoicePropertyName));
    }

    public function testOnNewDayEventWithInvalidChoiceShowsScreenWhenPointsRemaining(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 1);
        $stage = new Stage(owner: $character);
        $action = new Action(parameters: ["dk" => "invalid_option"]);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);
        $event->method(PropertyHook::get("stage"))->willReturn($stage);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->never())->method("addMaxHealth");

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->never())->method("setAttack");
        $stats->expects($this->never())->method("setDefense");

        $diceBag = $this->createStub(DiceBagInterface::class);

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())->method("resetActionGroups")->with($stage);

        $addedActions = [];
        $event->expects($this->exactly(3))
            ->method("addAction")
            ->willReturnCallback(function (string $group, Action $action) use (&$addedActions, $event) {
                $this->assertSame(ActionGroup::EMPTY, $group);
                $addedActions[] = $action;
                return $event;
            });

        $event->expects($this->once())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            diceBag: $diceBag,
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);

        $this->assertSame([], $character->getProperty(DragonCounterHandler::ChoicePropertyName, []));
        $this->assertSame("Dragon points", $stage->title);
        $this->assertArrayHasKey("lotgd.paragraph.DragonCounter.dragonPointsLeft", $stage->paragraphs);

        $paragraph = $stage->paragraphs["lotgd.paragraph.DragonCounter.dragonPointsLeft"];
        $this->assertInstanceOf(Paragraph::class, $paragraph);
        $this->assertSame(1, $paragraph->context["dragonPointsLeft"]);

        $this->assertCount(3, $addedActions);
        $this->assertSame("+5 Health", $addedActions[0]->title);
        $this->assertSame(["dk" => "health"], $addedActions[0]->parameters);
        $this->assertSame("lotgd2.action.DragonCounter.health", $addedActions[0]->reference);

        $this->assertSame("+1 Strength", $addedActions[1]->title);
        $this->assertSame(["dk" => "strength"], $addedActions[1]->parameters);
        $this->assertSame("lotgd2.action.DragonCounter.strength", $addedActions[1]->reference);

        $this->assertSame("+1 Defense", $addedActions[2]->title);
        $this->assertSame(["dk" => "defense"], $addedActions[2]->parameters);
        $this->assertSame("lotgd2.action.DragonCounter.defense", $addedActions[2]->reference);
    }

    public function testOnNewDayEventWithoutDkParameterShowsScreenWhenPointsRemaining(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 2, choices: [["choice" => "health"]]);
        $stage = new Stage(owner: $character);
        $action = new Action(parameters: []);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);
        $event->method(PropertyHook::get("stage"))->willReturn($stage);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->never())->method("addMaxHealth");

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->never())->method("setAttack");
        $stats->expects($this->never())->method("setDefense");

        $diceBag = $this->createStub(DiceBagInterface::class);

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())->method("resetActionGroups")->with($stage);

        $event->expects($this->exactly(3))
            ->method("addAction")
            ->with(ActionGroup::EMPTY, $this->isInstanceOf(Action::class))
            ->willReturnSelf();

        $event->expects($this->once())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            diceBag: $diceBag,
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);

        $this->assertSame("Dragon points", $stage->title);
        $this->assertArrayHasKey("lotgd.paragraph.DragonCounter.dragonPointsLeft", $stage->paragraphs);
        $paragraph = $stage->paragraphs["lotgd.paragraph.DragonCounter.dragonPointsLeft"];
        $this->assertSame(1, $paragraph->context["dragonPointsLeft"]);
    }

    public function testOnNewDayEventWithNoPointsLeftDoesNotModifyStageOrAddActions(): void
    {
        $character = $this->createCharacter(id: 999, dragonCounter: 1, choices: [["choice" => "health"]]);
        $action = new Action(parameters: []);

        $event = $this->createMock(StageChangeEvent::class);
        $event->method(PropertyHook::get("character"))->willReturn($character);
        $event->method(PropertyHook::get("action"))->willReturn($action);

        $stopwatch = $this->createMock(Stopwatch::class);
        $stopwatch->expects($this->once())->method("start")->with("lotgd2.DragonCounter.onNewDay");
        $stopwatch->expects($this->once())->method("stop")->with("lotgd2.DragonCounter.onNewDay");

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->never())->method("addMaxHealth");

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->never())->method("setAttack");
        $stats->expects($this->never())->method("setDefense");

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->never())->method("resetActionGroups");

        $event->expects($this->never())->method("addAction");
        $event->expects($this->never())->method("setStopRender");

        $handler = $this->createDragonCounterHandler(
            stopwatch: $stopwatch,
            character: $character,
            health: $health,
            stats: $stats,
            actionService: $actionService,
        );

        $handler->onNewDayEvent($event);
    }
}
