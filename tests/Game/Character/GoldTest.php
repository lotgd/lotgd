<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\GoldHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(GoldHandler::class)]
#[UsesClass(Character::class)]
#[AllowMockObjectsWithoutExpectations]
class GoldTest extends TestCase
{
    public function testGetGoldWithPropertyNotSetAndGameSettingIsNull(): void
    {
        $character = new Character();
        $gameStateService = $this->createMock(GameStateService::class);
        $gameStateService
            ->method('getSetting')
            ->with(GoldHandler::DefaultGoldGameSetting)
            ->willReturn(null);

        $loggerMock = $this->createStub(LoggerInterface::class);

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $this->assertEquals(0, $gold->getGold(null));
    }

    public function testGetGoldWithPropertyNotSetAndGameStateServiceReturnsValue(): void
    {
        $character = new Character();
        $gameStateService = $this->createMock(GameStateService::class);
        $gameStateService
            ->method('getSetting')
            ->with(GoldHandler::DefaultGoldGameSetting)
            ->willReturn(100);

        $loggerMock = $this->createStub(LoggerInterface::class);

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $this->assertEquals(100, $gold->getGold(null));
    }

    public static function getGoldProvider(): array
    {
        return [
            "no gold" => [0],
            "a bit gold" => [100],
            "a lot of gold" => [100_000_000],
        ];
    }

    #[DataProvider("getGoldProvider")]
    public function testGetGold(int $goldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $goldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $this->assertEquals($goldAmount, $gold->getGold(null));
    }

    public static function setGoldProvider(): array
    {
        return [
            [0, 100],
            [100, 0],
            [0, -100],
        ];
    }

    #[DataProvider("setGoldProvider")]
    public function testSetGold(int $initialGoldAmount, int $setGoldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $initialGoldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $gold->setGold(null, $setGoldAmount);
        $this->assertEquals($setGoldAmount, $gold->getGold(null));
    }

    public static function addGoldProvider(): array
    {
        return [
            [0, 100, 100],
            [100, 0, 100],
            [50, -100, -50],
        ];
    }

    #[DataProvider("addGoldProvider")]
    public function testAddGold(int $initialGoldAmount, int $setGoldAmount, int $finalGoldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $initialGoldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $gold->addGold(null, $setGoldAmount);
        $this->assertEquals($finalGoldAmount, $gold->getGold(null));
    }
}
