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
    #[TestWith([0])]
    #[TestWith([10])]
    public function testGetHealth(int $isHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $isHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertEquals($isHealth, $health->getHealth($character));
    }

    public function testGetHealthDefaultFallbackToLevel(): void
    {
        $character = new Character();
        $character->level = 4;

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame(40, $health->getHealth($character));
    }

    #[TestWith([0, 5, 5])]
    #[TestWith([10, 20, 20])]
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
        );

        $health->setHealth($character, $setHealth);

        $this->assertEquals($isNewHealth, $health->getHealth($character));
    }

    #[TestWith([10, 20, 5, 15],)]
    #[TestWith([10, 20, 10, 20],)]
    #[TestWith([10, 20, 15, 20],)]
    #[TestWith([10, 20, -5, 5],)]
    #[TestWith([10, 20, -10, 0],)]
    #[TestWith([10, 20, -15, 0],)]
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
        );

        $health->heal($character, $healAmount);
        $this->assertEquals($newHealth, $health->getHealth($character));
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
        );

        $health->heal($character);

        $this->assertEquals($expectedHealth, $health->getHealth($character));
    }

    #[TestWith([0])]
    #[TestWith([10])]
    public function testGetMaxHealth(int $isHealth): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::MaxHealthPropertyName => $isHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertEquals($isHealth, $health->getMaxHealth($character));
    }

    public function testGetMaxHealthDefaultFallbackToLevel(): void
    {
        $character = new Character();
        $character->level = 5;

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame(50, $health->getMaxHealth($character));
    }

    #[TestWith([0, 5, 5])]
    #[TestWith([10, 20, 20])]
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
        );

        $health->setMaxHealth($character, $setMaxHealth);

        $this->assertEquals($isNewMaxHealth, $health->getMaxHealth($character));
    }

    #[TestWith([10, true], "Is Alive")]
    #[TestWith([0, false], "Is Dead")]
    public function testIsAlive(int $setHealth, bool $aliveStatus): void
    {
        $character = new Character();
        $character->properties = [
            HealthHandler::HealthPropertyName => $setHealth,
        ];

        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertEquals($aliveStatus, $health->isAlive($character));
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
        );

        $health->addMaxHealth($character, $addMaxHealth);

        $this->assertEquals($expectedMaxHealth, $health->getMaxHealth($character));
    }

    public function testResurrections(): void
    {
        $character = new Character();
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame(10, $health->getResurrections($character));

        $health->addResurrection($character);
        $this->assertSame(11, $health->getResurrections($character));
    }

    public function testAge(): void
    {
        $character = new Character();
        $health = new HealthHandler(
            $this->createStub(GameStateService::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame(0, $health->getAge($character));

        $health->addAge($character);
        $this->assertSame(1, $health->getAge($character));
    }

    public function testTurns(): void
    {
        $gameStateService = $this->createMock(GameStateService::class);
        $character = new Character();

        $health = new HealthHandler(
            $gameStateService,
            $this->createStub(LoggerInterface::class),
        );

        $gameStateService->method("getSetting")
            ->with(HealthHandler::DefaultTurnsGameSetting)
            ->willReturn(35);

        $this->assertSame(35, $health->getMaxTurns($character));
        $this->assertSame(35, $health->getTurns($character));

        // Test with game setting returning null fallback 30
        $gameStateService2 = $this->createMock(GameStateService::class);
        $gameStateService2->method("getSetting")
            ->with(HealthHandler::DefaultTurnsGameSetting)
            ->willReturn(null);

        $health2 = new HealthHandler(
            $gameStateService2,
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame(30, $health2->getMaxTurns($character));

        // Set turns explicit
        $health->setTurns($character, 15);
        $this->assertSame(15, $health->getTurns($character));

        // Set turns negative clamps to 0
        $health->setTurns($character, -10);
        $this->assertSame(0, $health->getTurns($character));

        // Set turns null defaults to max turns
        $health->setTurns($character, null);
        $this->assertSame(35, $health->getTurns($character));

        // Add turns
        $health->addTurns($character, 5);
        $this->assertSame(40, $health->getTurns($character));

        // Decrement turns
        $health->decrementTurns($character);
        $this->assertSame(39, $health->getTurns($character));
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
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method("add")
            ->with(HealthHandler::DefaultTurnsGameSetting, IntegerType::class, $this->isArray());

        $event = new FormExtensionEvent($builder, []);
        $health->onGameSettingsFormExtension($event);
    }
}
