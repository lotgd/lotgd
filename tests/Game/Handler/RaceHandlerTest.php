<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use Exception;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Race as RaceEntity;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Handler\RaceHandler;
use LotGD2\Game\Race\RaceInterface;
use LotGD2\Game\Race\StandardRace;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Kernel;
use LotGD2\Repository\RaceRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[CoversClass(RaceHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(RaceEntity::class)]
#[UsesClass(Stage::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(Scene::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(StageChangeEvent::class)]
class RaceHandlerTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $race
     */
    private function createCharacter(
        ?string $name = "Hero",
        ?int $id = 123,
        ?array $race = null,
    ): Character {
        $character = new Character(name: $name);
        if ($id !== null) {
            $reflection = new ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }
        if ($race !== null) {
            $character->setProperty(RaceHandler::RaceProperty, $race);
        }

        return $character;
    }

    private function createRaceHandler(
        ?Kernel $kernel = null,
        ?LoggerInterface $logger = null,
        ?Stopwatch $stopwatch = null,
        ?RaceRepository $raceRepository = null,
    ): RaceHandler {
        return new RaceHandler(
            $kernel ?? $this->createStub(Kernel::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $stopwatch ?? new Stopwatch(),
            $raceRepository ?? $this->createStub(RaceRepository::class),
        );
    }

    private function setRaceId(RaceEntity $race, int $id): RaceEntity
    {
        $reflection = new ReflectionProperty(RaceEntity::class, "id");
        $reflection->setValue($race, $id);
        return $race;
    }

    public function testConstants(): void
    {
        $this->assertSame("race", RaceHandler::RaceProperty);
    }

    public function testOnNewDayEventWithoutActionParameterAndNoRace(): void
    {
        $race1 = $this->setRaceId(new RaceEntity(
            name: "Human",
            description: "Human desc",
            selectionText: "You were born human",
            className: StandardRace::class,
        ), 1);

        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->expects($this->once())
            ->method("findAll")
            ->willReturn([$race1]);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $action = new Action();
        $scene = new Scene();
        $event = new StageChangeEvent($stage, $action, $scene);

        $handler = $this->createRaceHandler(raceRepository: $raceRepository);
        $handler->onNewDayEvent($event);

        $this->assertTrue($event->stopRender);
        $this->assertSame("Where to you recall growing up?", $stage->title);
    }

    public function testOnNewDayEventWithRaceActionParameterAndSelectionSuccess(): void
    {
        $race = $this->setRaceId(new RaceEntity(
            name: "Elf",
            description: "Elf desc",
            selectionText: "You were born an elf",
            className: StandardRace::class,
        ), 2);

        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->expects($this->atLeastOnce())
            ->method("find")
            ->with(2)
            ->willReturn($race);

        $raceClassMock = $this->createMock(RaceInterface::class);
        $raceClassMock->expects($this->once())
            ->method("onSelect");

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method("get")
            ->with(StandardRace::class)
            ->willReturn($raceClassMock);

        $kernel = $this->createStub(Kernel::class);
        $kernel->method("getContainer")->willReturn($container);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->addActionGroup(new ActionGroup(ActionGroup::EMPTY, ""));
        $stage->owner = $character;
        $action = new Action(parameters: ["race" => 2]);
        $scene = new Scene();
        $event = new StageChangeEvent($stage, $action, $scene);

        $handler = $this->createRaceHandler(kernel: $kernel, raceRepository: $raceRepository);
        $handler->onNewDayEvent($event);

        $this->assertTrue($event->stopRender);
        $this->assertSame("Where to you recall growing up?", $stage->title);
        $this->assertSame("You were born an elf", array_values($stage->paragraphs)[0]->text);
        $this->assertTrue($handler->hasRace($character));
    }

    public function testDoRaceSelectionWhenRaceDisappeared(): void
    {
        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->expects($this->once())
            ->method("find")
            ->with(99)
            ->willReturn(null);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $action = new Action(parameters: ["race" => 99]);
        $scene = new Scene();
        $event = new StageChangeEvent($stage, $action, $scene);

        $handler = $this->createRaceHandler(raceRepository: $raceRepository);
        $result = $handler->doRaceSelection($event);

        $this->assertFalse($result);
    }

    public function testShowRaceSelectionWhenNoRacesAvailable(): void
    {
        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->expects($this->once())
            ->method("findAll")
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("info")
            ->with("There are no races available. Skipping race selection.");

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $event = new StageChangeEvent($stage, new Action(), new Scene());

        $handler = $this->createRaceHandler(logger: $logger, raceRepository: $raceRepository);
        $result = $handler->showRaceSelection($event);

        $this->assertFalse($result);
    }

    public function testGettersWhenNoRaceSet(): void
    {
        $character = $this->createCharacter();
        $handler = $this->createRaceHandler();

        $this->assertNull($handler->getRaceProperty($character));
        $this->assertFalse($handler->hasRace($character));
        $this->assertNull($handler->getRace($character));
        $this->assertNull($handler->getRaceName($character));
        $this->assertNull($handler->getRaceClass($character));
        $this->assertNull($handler->getRaceConfiguration($character));
    }

    public function testGettersWhenRaceSet(): void
    {
        $character = $this->createCharacter(race: [
            "id" => 5,
            "name" => "Dwarf",
            "className" => StandardRace::class,
            "configuration" => ["attack" => 2, "defense" => 1],
        ]);
        $race = $this->setRaceId(new RaceEntity(
            name: "Dwarf",
            description: "Dwarf desc",
            selectionText: "Dwarf sel",
            className: StandardRace::class,
            configuration: ["attack" => 2, "defense" => 1],
        ), 5);

        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->method("find")
            ->with(5)
            ->willReturn($race);

        $handler = $this->createRaceHandler(raceRepository: $raceRepository);

        $this->assertTrue($handler->hasRace($character));
        $this->assertSame($race, $handler->getRace($character));
        $this->assertSame("Dwarf", $handler->getRaceName($character));
        $this->assertSame(StandardRace::class, $handler->getRaceClass($character));
        $this->assertSame(["attack" => 2, "defense" => 1], $handler->getRaceConfiguration($character));
    }

    public function testSetRaceWithNullRemovesRace(): void
    {
        $character = $this->createCharacter(race: ["id" => 1]);

        $handler = $this->createRaceHandler();
        $handler->setRace($character, null);

        $this->assertSame([], $handler->getRaceProperty($character));
    }

    public function testSetRaceWithNullClassName(): void
    {
        $character = $this->createCharacter();
        $race = $this->setRaceId(new RaceEntity(
            name: "Custom",
            description: "Desc",
            selectionText: "Sel",
            className: null,
        ), 10);

        $handler = $this->createRaceHandler();
        $handler->setRace($character, $race);

        $this->assertSame(10, $handler->getRaceProperty($character)["id"]);
    }

    public function testSetRaceWithNonExistentClassLogsCritical(): void
    {
        $character = $this->createCharacter();
        $race = $this->setRaceId(new RaceEntity(
            name: "Custom",
            description: "Desc",
            selectionText: "Sel",
        ), 11);

        $reflection = new ReflectionProperty(RaceEntity::class, "className");
        $reflection->setRawValue($race, "NonExistentRaceClass12345");

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("critical")
            ->with("The race class NonExistentRaceClass12345 does not exist.");

        $handler = $this->createRaceHandler(logger: $logger);
        $handler->setRace($character, $race);
    }

    public function testSetRaceWithContainerExceptionLogsCritical(): void
    {
        $character = $this->createCharacter();
        $race = $this->setRaceId(new RaceEntity(
            name: "Human",
            description: "Desc",
            selectionText: "Sel",
            className: StandardRace::class,
        ), 1);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method("get")
            ->with(StandardRace::class)
            ->willThrowException(new Exception("Container DI failure"));

        $kernel = $this->createStub(Kernel::class);
        $kernel->method("getContainer")->willReturn($container);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("critical")
            ->with($this->stringContains("There was an issue with the race class"));

        $handler = $this->createRaceHandler(kernel: $kernel, logger: $logger);
        $handler->setRace($character, $race);
    }

    public function testSetRaceWithNonRaceInterfaceInstanceInContainer(): void
    {
        $character = $this->createCharacter();
        $race = $this->setRaceId(new RaceEntity(
            name: "Human",
            description: "Desc",
            selectionText: "Sel",
            className: StandardRace::class,
        ), 1);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method("get")
            ->with(StandardRace::class)
            ->willReturn(new stdClass());

        $kernel = $this->createStub(Kernel::class);
        $kernel->method("getContainer")->willReturn($container);

        $handler = $this->createRaceHandler(kernel: $kernel);
        $handler->setRace($character, $race);

        $this->assertSame(1, $handler->getRaceProperty($character)["id"]);
    }

    public function testOnCharacterResetWithValidClass(): void
    {
        $character = $this->createCharacter(race: [
            "id" => 1,
            "name" => "Human",
            "className" => StandardRace::class,
            "configuration" => ["turns" => 5],
        ]);
        $race = $this->setRaceId(new RaceEntity(
            name: "Human",
            description: "Desc",
            selectionText: "Sel",
            className: StandardRace::class,
            configuration: ["turns" => 5],
        ), 1);

        $raceRepository = $this->createMock(RaceRepository::class);
        $raceRepository->method("find")
            ->with(1)
            ->willReturn($race);

        $raceClassMock = $this->createMock(RaceInterface::class);
        $raceClassMock->expects($this->once())
            ->method("onDeselect")
            ->with($character, $race, ["turns" => 5]);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method("get")
            ->with(StandardRace::class)
            ->willReturn($raceClassMock);

        $kernel = $this->createStub(Kernel::class);
        $kernel->method("getContainer")->willReturn($container);

        $event = new CharacterChangeEvent($character, $character);

        $handler = $this->createRaceHandler(kernel: $kernel, raceRepository: $raceRepository);
        $handler->onCharacterReset($event);

        $this->assertSame([], $handler->getRaceProperty($character));
    }

    public function testOnCharacterResetWithInvalidClassLogsCritical(): void
    {
        $character = $this->createCharacter(race: [
            "id" => 1,
            "name" => "Human",
            "className" => "NonExistentClass999",
            "configuration" => [],
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("critical")
            ->with($this->stringContains("The race class NonExistentClass999 does not exist"));

        $event = new CharacterChangeEvent($character, $character);

        $handler = $this->createRaceHandler(logger: $logger);
        $handler->onCharacterReset($event);

        $this->assertSame([], $handler->getRaceProperty($character));
    }

    public function testOnCharacterResetWithNullClassNameLogsCritical(): void
    {
        $character = $this->createCharacter(race: [
            "id" => 1,
            "name" => "Custom",
            "className" => null,
            "configuration" => [],
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("critical")
            ->with($this->stringContains("The race class  does not exist"));

        $event = new CharacterChangeEvent($character, $character);

        $handler = $this->createRaceHandler(logger: $logger);
        $handler->onCharacterReset($event);

        $this->assertSame([], $handler->getRaceProperty($character));
    }
}
