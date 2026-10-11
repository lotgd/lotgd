<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

#[CoversClass(HealthHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(Stage::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Action::class)]
#[UsesClass(Scene::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(FormExtensionEvent::class)]
#[UsesClass(StageChangeEvent::class)]
#[UsesClass(DiceBag::class)]
#[AllowMockObjectsWithoutExpectations]
final class HealthTest extends TestCase
{
    public static function getHealthProvider(): array
    {
        return [
            [0],
            [10],
        ];
    }

    #[DataProvider("getHealthProvider")]
    public function testGetHealth(int $isHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $isHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
            $character,
        );

        $this->assertEquals($isHealth, $health->getHealth());
    }

    public function testGetHealthDefaultFallbackToLevel(): void
    {
        $character = new Character();
        $character->level = 4;

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $character,
        );

        $this->assertSame(40, $health->getHealth());
    }

    public static function setHealthProvider(): array
    {
        return [
            [0, 5, 5],
            [10, 20, 20],
        ];
    }

    #[DataProvider("setHealthProvider")]
    public function testSetHealth(int $isHealth, int $setHealth, int $isNewHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $isHealth,
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $loggerMock,
            $character,
        );

        $health->setHealth($setHealth);

        $this->assertEquals($isNewHealth, $health->getHealth());
    }

    public function testSetHealthWithExplicitCharacter(): void
    {
        $defaultCharacter = new Character();
        $explicitCharacter = new Character();

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $loggerMock,
            $defaultCharacter,
        );

        $health->setHealth(42, $explicitCharacter);

        $this->assertSame(42, $health->getHealth($explicitCharacter));
        $this->assertSame(10, $health->getHealth($defaultCharacter));
    }

    public static function healProvider(): array
    {
        return [
            [10, 20, 5, 15],
            [10, 20, 10, 20],
            [10, 20, 15, 20],
            [10, 20, -5, 5],
            [10, 20, -10, 0],
            [10, 20, -15, 0],
        ];
    }

    #[DataProvider("healProvider")]
    public function testHeal(int $isHealth, int $isMaxHealth, int $healAmount, int $newHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $isHealth,
            HealthHandler::MaxHealthPropertyName => $isMaxHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
            $character
        );

        $health->heal($healAmount);
        $this->assertEquals($newHealth, $health->getHealth());
    }

    #[TestWith([0, 200, 200])]
    #[TestWith([20, 180, 180])]
    public function testFullHeal(int $isHealth, int $maxHealth, int $expectedHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $isHealth,
            HealthHandler::MaxHealthPropertyName => $maxHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
            $character,
        );
        $health->heal();

        $this->assertEquals($expectedHealth, $health->getHealth());
    }

    public function testHealWithExplicitCharacter(): void
    {
        $defaultCharacter = new Character();
        $explicitCharacter = new Character();
        $explicitCharacter->properties = [
            HealthHandler::HealthPropertyName => 10,
            HealthHandler::MaxHealthPropertyName => 50,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $defaultCharacter,
        );

        $health->heal(20, $explicitCharacter);
        $this->assertSame(30, $health->getHealth($explicitCharacter));
    }

    #[DataProvider("getHealthProvider")]
    public function testGetMaxHealth(int $isHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::MaxHealthPropertyName => $isHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
            $character,
        );

        $this->assertEquals($isHealth, $health->getMaxHealth());
    }

    public function testGetMaxHealthDefaultFallbackToLevel(): void
    {
        $character = new Character();
        $character->level = 5;

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $character,
        );

        $this->assertSame(50, $health->getMaxHealth());
    }

    #[DataProvider("setHealthProvider")]
    public function testSetMaxHealth(int $isMaxHealth, int $setMaxHealth, int $isNewMaxHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::MaxHealthPropertyName => $isMaxHealth,
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $loggerMock,
            $character,
        );

        $health->setMaxHealth($setMaxHealth);

        $this->assertEquals($isNewMaxHealth, $health->getMaxHealth());
    }

    public function testSetMaxHealthWithExplicitCharacter(): void
    {
        $defaultCharacter = new Character();
        $explicitCharacter = new Character();

        $loggerMock = $this->createMock(LoggerInterface::class);
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $loggerMock,
            $defaultCharacter,
        );

        $health->setMaxHealth(75, $explicitCharacter);
        $this->assertSame(75, $health->getMaxHealth($explicitCharacter));
    }

    public static function isAliveProvider(): array
    {
        return [
            "Is Alive" => [10, true],
            "Is Dead" => [0, false],
        ];
    }

    #[DataProvider("isAliveProvider")]
    public function testIsAlive(int $setHealth, bool $aliveStatus): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $setHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $character
        );

        $this->assertEquals($aliveStatus, $health->isAlive());

        $explicitCharacter = new Character();
        $explicitCharacter->properties = [
            HealthHandler::HealthPropertyName => $setHealth,
        ];
        $this->assertEquals($aliveStatus, $health->isAlive($explicitCharacter));
    }

    #[TestWith([10, 10, 20], "positiveAmount")]
    #[TestWith([10, -10, 0], "negativeAmount")]
    public function testAddMaxHealth(int $initialMaxHealth, int $addMaxHealth, int $expectedMaxHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::MaxHealthPropertyName => $initialMaxHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
            $character,
        );

        $health->addMaxHealth($addMaxHealth);

        $this->assertEquals($expectedMaxHealth, $health->getMaxHealth());

        $explicitCharacter = new Character();
        $explicitCharacter->properties = [
            HealthHandler::MaxHealthPropertyName => $initialMaxHealth,
        ];
        $health->addMaxHealth($addMaxHealth, $explicitCharacter);
        $this->assertEquals($expectedMaxHealth, $health->getMaxHealth($explicitCharacter));
    }

    public function testResurrections(): void
    {
        $character = new Character();
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $character,
        );

        $this->assertSame(10, $health->getResurrections());

        $health->addResurrection();
        $this->assertSame(11, $health->getResurrections());

        $explicitCharacter = new Character();
        $this->assertSame(10, $health->getResurrections($explicitCharacter));
        $health->addResurrection($explicitCharacter);
        $this->assertSame(11, $health->getResurrections($explicitCharacter));
    }

    public function testAge(): void
    {
        $character = new Character();
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            $character,
        );

        $this->assertSame(0, $health->getAge());

        $health->addAge();
        $this->assertSame(1, $health->getAge());

        $explicitCharacter = new Character();
        $this->assertSame(0, $health->getAge($explicitCharacter));
        $health->addAge($explicitCharacter);
        $this->assertSame(1, $health->getAge($explicitCharacter));
    }

    public function testTurns(): void
    {
        $gameStateService = $this->createMock(GameStateService::class);
        $character = new Character();

        $health = new HealthHandler(
            $gameStateService,
            $this->createStub(LoggerInterface::class),
            $character,
        );

        $gameStateService->method("getSetting")
            ->with(HealthHandler::DefaultTurnsGameSetting)
            ->willReturn(35);

        $this->assertSame(35, $health->getMaxTurns());
        $this->assertSame(35, $health->getTurns());

        // Test with game setting returning null fallback 30
        $gameStateService2 = $this->createMock(GameStateService::class);
        $gameStateService2->method("getSetting")
            ->with(HealthHandler::DefaultTurnsGameSetting)
            ->willReturn(null);

        $health2 = new HealthHandler(
            $gameStateService2,
            $this->createStub(LoggerInterface::class),
            $character,
        );
        $this->assertSame(30, $health2->getMaxTurns());

        // Set turns explicit
        $health->setTurns(15);
        $this->assertSame(15, $health->getTurns());

        // Set turns negative clamps to 0
        $health->setTurns(-10);
        $this->assertSame(0, $health->getTurns());

        // Set turns null defaults to max turns
        $health->setTurns(null);
        $this->assertSame(35, $health->getTurns());

        // Add turns
        $health->addTurns(5, null);
        $this->assertSame(40, $health->getTurns());

        // Decrement turns
        $health->decrementTurns();
        $this->assertSame(39, $health->getTurns());

        // Operations with explicit character
        $explicitCharacter = new Character();
        $explicitCharacter->properties = [HealthHandler::Turns => 20];
        $this->assertSame(20, $health->getTurns($explicitCharacter));
        $health->decrementTurns($explicitCharacter);
        $this->assertSame(19, $health->getTurns($explicitCharacter));
        $health->addTurns(3, $explicitCharacter);
        $this->assertSame(22, $health->getTurns($explicitCharacter));
    }

    public function testOnNewDayEvent(): void
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => 0, // dead prior to new day
            HealthHandler::MaxHealthPropertyName => 100,
            HealthHandler::Age => 5,
            HealthHandler::Resurrections => 2,
        ];

        $healthHandler = new HealthHandler($gameStateService, $logger, $character);

        $stage = new Stage();
        $stage->owner = $character;
        $action = new Action();
        $scene = new Scene();
        $event = new StageChangeEvent($stage, $action, $scene);

        $healthHandler->onNewDayEvent($event);

        $this->assertSame(6, $healthHandler->getAge($character));
        $this->assertSame(3, $healthHandler->getResurrections($character));
        $this->assertSame(100, $healthHandler->getHealth($character));

        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(4, $paragraphs);
        $this->assertSame("lotgd.paragraph.Health.newDay.age", $paragraphs[0]->id);
        $this->assertSame("lotgd.paragraph.Health.newDay.turns", $paragraphs[1]->id);
        $this->assertSame("lotgd.paragraph.Health.newDay.resurrection", $paragraphs[2]->id);
        $this->assertSame("lotgd.paragraph.Health.newDay.heal", $paragraphs[3]->id);

        // When character is already alive, no resurrection paragraph
        $character2 = new Character();
        $character2->properties = [
            HealthHandler::HealthPropertyName => 50,
            HealthHandler::MaxHealthPropertyName => 100,
        ];
        $stage2 = new Stage();
        $stage2->owner = $character2;
        $event2 = new StageChangeEvent($stage2, $action, $scene);

        $healthHandler->onNewDayEvent($event2);
        $paragraphs2 = array_values($stage2->paragraphs);
        $this->assertCount(3, $paragraphs2);
    }

    public function testOnCharacterLevelIncrease(): void
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = new Character();
        $character->level = 3;
        $character->properties = [
            HealthHandler::HealthPropertyName => 20,
            HealthHandler::MaxHealthPropertyName => 20,
        ];

        $characterBefore = new Character();
        $characterBefore->level = 1;

        $healthHandler = new HealthHandler($gameStateService, $logger, $character);

        $stage = new Stage();
        $event = new CharacterChangeEvent($character, $characterBefore, $stage);

        $healthHandler->onCharacterLevelIncrease($event);

        $this->assertSame(40, $healthHandler->getMaxHealth($character));
        $this->assertSame(40, $healthHandler->getHealth($character));

        // When delta level <= 0
        $characterBefore2 = new Character();
        $characterBefore2->level = 3;
        $event2 = new CharacterChangeEvent($character, $characterBefore2, $stage);

        $healthHandler->onCharacterLevelIncrease($event2);
        $this->assertSame(40, $healthHandler->getMaxHealth($character));
    }

    public function testOnCharacterReset(): void
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = new Character();
        $character->level = 1;
        $character->properties = [
            HealthHandler::HealthPropertyName => 100,
            HealthHandler::MaxHealthPropertyName => 100,
        ];

        $characterBefore = new Character();
        $characterBefore->level = 10;

        $healthHandler = new HealthHandler($gameStateService, $logger, $character);

        $event = new CharacterChangeEvent($character, $characterBefore);
        $healthHandler->onCharacterReset($event);

        $this->assertSame(10, $healthHandler->getMaxHealth($character));
        $this->assertSame(10, $healthHandler->getHealth($character));

        // When delta level <= 0
        $characterBefore2 = new Character();
        $characterBefore2->level = 1;
        $event2 = new CharacterChangeEvent($character, $characterBefore2);

        $healthHandler->onCharacterReset($event2);
        $this->assertSame(10, $healthHandler->getMaxHealth($character));
    }

    public function testOnGameSettingsFormExtension(): void
    {
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
            null,
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method("add")
            ->with(HealthHandler::DefaultTurnsGameSetting, IntegerType::class, $this->isArray());

        $event = new FormExtensionEvent($builder, []);
        $health->onGameSettingsFormExtension($event);
    }
}
