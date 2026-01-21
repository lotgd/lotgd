<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Gold;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Gold::class)]
#[UsesClass(Character::class)]
class GoldTest extends TestCase
{
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
            Gold::PropertyName => $goldAmount,
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);

        $gold = new Gold($loggerMock, $character);

        $this->assertEquals($goldAmount, $gold->getGold());
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
            Gold::PropertyName => $initialGoldAmount,
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new Gold($loggerMock, $character);

        $gold->setGold($setGoldAmount);
        $this->assertEquals($setGoldAmount, $gold->getGold());
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
            Gold::PropertyName => $initialGoldAmount,
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new Gold($loggerMock, $character);

        $gold->addGold($setGoldAmount);
        $this->assertEquals($finalGoldAmount, $gold->getGold());
    }
}
