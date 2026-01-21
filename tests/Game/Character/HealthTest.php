<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Health;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Health::class)]
#[UsesClass(Character::class)]
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
        $character->setProperties([
            Health::HealthPropertyName => $isHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);

        $this->assertEquals($isHealth, $health->getHealth());
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
        $character->setProperties([
            Health::HealthPropertyName => $isHealth,
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $health = new Health($loggerMock, $character);

        $health->setHealth($setHealth);

        $this->assertEquals($isNewHealth, $health->getHealth());
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
        $character->setProperties([
            Health::HealthPropertyName => $isHealth,
            Health::MaxHealthPropertyName => $isMaxHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);

        $health->heal( $healAmount);
        $this->assertEquals($newHealth, $health->getHealth());
    }

    #[TestWith([0, 200, 200])]
    #[TestWith([20, 180, 180])]
    public function testFullHeal(int $isHealth, int $maxHealth, int $expectedHealth): void
    {
        $character = new Character();
        $character->setProperties([
            Health::HealthPropertyName => $isHealth,
            Health::MaxHealthPropertyName => $maxHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);
        $health->heal();

        $this->assertEquals($expectedHealth, $health->getHealth());
    }

    #[DataProvider("getHealthProvider")]
    public function testGetMaxHealth(int $isHealth): void
    {
        $character = new Character();
        $character->setProperties([
            Health::MaxHealthPropertyName => $isHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);

        $this->assertEquals($isHealth, $health->getMaxHealth());
    }

    #[DataProvider("setHealthProvider")]
    public function testSetMaxHealth(int $isMaxHealth, int $setMaxHealth, int $isNewMaxHealth): void
    {
        $character = new Character();
        $character->setProperties([
            Health::MaxHealthPropertyName => $isMaxHealth,
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $health = new Health($loggerMock, $character);

        $health->setMaxHealth($setMaxHealth);

        $this->assertEquals($isNewMaxHealth, $health->getMaxHealth());
    }

    public static function isAliveProvider(): array
    {
        return [
            "Is Alive" => [10, true],
            "Is Dead" => [0, false],
        ];
    }

    #[DataProvider("isAliveProvider")]
    public function testIsAlive(int $setHealth, bool $aliveStatus)
    {
        $character = new Character();
        $character->setProperties([
            Health::HealthPropertyName => $setHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);

        $this->assertEquals($aliveStatus, $health->isAlive());
    }

    #[TestWith([10, 10, 20], "positiveAmount")]
    #[TestWith([10, -10, 0], "negativeAmount")]
    public function testAddMaxHealth(int $initialMaxHealth, int $addMaxHealth, int $expectedMaxHealth)
    {
        $character = new Character();
        $character->setProperties([
            Health::MaxHealthPropertyName => $initialMaxHealth,
        ]);

        $health = new Health($this->createMock(LoggerInterface::class), $character);

        $health->addMaxHealth($addMaxHealth);

        $this->assertEquals($expectedMaxHealth, $health->getMaxHealth());
    }
}
