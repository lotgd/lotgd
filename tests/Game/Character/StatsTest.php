<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(StatsHandler::class)]
#[AllowMockObjectsWithoutExpectations]
class StatsTest extends TestCase
{
    private StatsHandler $stats;
    private Character $character;
    private EquipmentHandler $equipment;
    private ?LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->character = $this->createMock(Character::class);
        $this->equipment = $this->createMock(EquipmentHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->stats = new StatsHandler(
            $this->logger,
            $this->equipment,
            $this->character
        );
    }

    public function testGetExperienceReturnsDefaultZeroWhenNotSet(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::ExperiencePropertyName, 0)
            ->willReturn(0);

        $this->assertEquals(0, $this->stats->getExperience());
    }

    public function testGetExperienceReturnsSetValue(): void
    {
        $expectedExperience = 1000;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::ExperiencePropertyName, 0)
            ->willReturn($expectedExperience);

        $this->assertEquals($expectedExperience, $this->stats->getExperience());
    }

    public function testSetExperienceCallsCharacterSetProperty(): void
    {
        $experience = 500;
        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::ExperiencePropertyName, $experience);

        $result = $this->stats->setExperience($experience);

        $this->assertSame($this->stats, $result);
    }

    public function testSetExperienceReturnsStaticForChaining(): void
    {
        $this->character
            ->expects($this->once())
            ->method('setProperty');

        $result = $this->stats->setExperience(100);

        $this->assertInstanceOf(StatsHandler::class, $result);
    }

    public function testAddExperienceAddsToCurrentValue(): void
    {
        $currentExperience = 100;
        $addedExperience = 50;
        $expectedTotal = 150;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::ExperiencePropertyName, 0)
            ->willReturn($currentExperience);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::ExperiencePropertyName, $expectedTotal);

        $result = $this->stats->addExperience($addedExperience);

        $this->assertSame($this->stats, $result);
    }

    public function testAddExperienceWithZeroCurrentValue(): void
    {
        $addedExperience = 100;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::ExperiencePropertyName, 0)
            ->willReturn(0);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::ExperiencePropertyName, $addedExperience);

        $this->stats->addExperience($addedExperience);
    }

    public function testAddExperienceReturnsStaticForChaining(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->willReturn(0);

        $this->character
            ->expects($this->once())
            ->method('setProperty');

        $result = $this->stats->addExperience(50);

        $this->assertInstanceOf(StatsHandler::class, $result);
    }

    public function testGetLevelCallsCharacterGetLevel(): void
    {
        $expectedLevel = 5;
        $this->character
            ->expects($this->once())
            ->method(PropertyHook::get("level"))
            ->willReturn($expectedLevel);

        $this->assertEquals($expectedLevel, $this->stats->getLevel());
    }

    public function testGetAttackReturnsDefaultValueOne(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn(1);

        $this->assertEquals(1, $this->stats->getAttack());
    }

    public function testGetAttackReturnsSetValue(): void
    {
        $expectedAttack = 10;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn($expectedAttack);

        $this->assertEquals($expectedAttack, $this->stats->getAttack());
    }

    public function testSetAttackCallsCharacterSetProperty(): void
    {
        $attack = 15;
        $currentAttack = 10;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn($currentAttack);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::AttackPropertyName, $attack);


        $this->character
            ->expects($this->once())
            ->method("__toString")
            ->willReturn("<Character 1>");

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('<Character 1>: attack set to 15 (was 10) before).');

        $result = $this->stats->setAttack($attack);

        $this->assertSame($this->stats, $result);
    }

    public function testSetAttackWithNullLogger(): void
    {
        $statsWithNullLogger = new StatsHandler(
            null,
            $this->equipment,
            $this->character
        );

        $attack = 15;

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::AttackPropertyName, $attack);

        $result = $statsWithNullLogger->setAttack($attack);

        $this->assertSame($statsWithNullLogger, $result);
    }

    public function testAddAttackAddsToCurrentValue(): void
    {
        $currentAttack = 10;
        $addedAttack = 5;
        $expectedTotal = 15;

        $this->character
            ->expects($this->exactly(2))
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn($currentAttack, $currentAttack); // Called twice: once in getAttack, once in setAttack debug

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::AttackPropertyName, $expectedTotal);

        $result = $this->stats->addAttack($addedAttack);

        $this->assertSame($this->stats, $result);
    }

    public function testGetDefenseReturnsDefaultValueOne(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::DefensePropertyName, 1)
            ->willReturn(1);

        $this->assertEquals(1, $this->stats->getDefense());
    }

    public function testGetDefenseReturnsSetValue(): void
    {
        $expectedDefense = 8;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::DefensePropertyName, 1)
            ->willReturn($expectedDefense);

        $this->assertEquals($expectedDefense, $this->stats->getDefense());
    }

    public function testSetDefenseCallsCharacterSetProperty(): void
    {
        $defense = 12;
        $currentDefense = 8;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::DefensePropertyName, 1)
            ->willReturn($currentDefense);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::DefensePropertyName, $defense);

        $this->character
            ->expects($this->once())
            ->method("__toString")
            ->willReturn("<Character 1>");

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('<Character 1>: defense set to 12 (was 8) before).');

        $result = $this->stats->setDefense($defense);

        $this->assertSame($this->stats, $result);
    }

    public function testSetDefenseWithNullLogger(): void
    {
        $statsWithNullLogger = new StatsHandler(
            null,
            $this->equipment,
            $this->character
        );

        $defense = 12;

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::DefensePropertyName, $defense);

        $result = $statsWithNullLogger->setDefense($defense);

        $this->assertSame($statsWithNullLogger, $result);
    }

    public function testAddDefenseAddsToCurrentValue(): void
    {
        $currentDefense = 8;
        $addedDefense = 3;

        // Note: There's a bug in the original code - addDefense calls setAttack instead of setDefense
        // This test reflects the current (buggy) behavior
        $this->character
            ->expects($this->exactly(2))
            ->method('getProperty')
            ->willReturnMap([
                [StatsHandler::DefensePropertyName, 1, $currentDefense],
                [StatsHandler::DefensePropertyName, 1, 10]
            ]);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::DefensePropertyName, 11);

        $result = $this->stats->addDefense($addedDefense);

        $this->assertSame($this->stats, $result);
    }

    public function testGetTotalAttackWithNoWeapon(): void
    {
        $baseAttack = 5;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn($baseAttack);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::WeaponSlot)
            ->willReturn(null);

        $this->assertEquals($baseAttack, $this->stats->getTotalAttack());
    }

    public function testGetTotalAttackWithWeapon(): void
    {
        $baseAttack = 5;
        $weaponValue = 10;
        $expectedTotal = 15;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::AttackPropertyName, 1)
            ->willReturn($baseAttack);

        $weapon = $this->createMock(EquipmentItem::class);
        $weapon
            ->expects($this->once())
            ->method('getStrength')
            ->willReturn($weaponValue);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::WeaponSlot)
            ->willReturn($weapon);

        $this->assertEquals($expectedTotal, $this->stats->getTotalAttack());
    }

    public function testGetTotalDefenseWithNoArmor(): void
    {
        $baseDefense = 3;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::DefensePropertyName, 1)
            ->willReturn($baseDefense);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::ArmorSlot)
            ->willReturn(null);

        $this->assertEquals($baseDefense, $this->stats->getTotalDefense());
    }

    public function testGetTotalDefenseWithArmor(): void
    {
        $baseDefense = 3;
        $armorValue = 7;
        $expectedTotal = 10;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::DefensePropertyName, 1)
            ->willReturn($baseDefense);

        $armor = $this->createMock(EquipmentItem::class);
        $armor
            ->expects($this->once())
            ->method('getStrength')
            ->willReturn($armorValue);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::ArmorSlot)
            ->willReturn($armor);

        $this->assertEquals($expectedTotal, $this->stats->getTotalDefense());
    }

    public function testMultipleExperienceAdditions(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(StatsHandler::ExperiencePropertyName, 0)
            ->willReturn(100);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(StatsHandler::ExperiencePropertyName, 150);

        $this->stats->addExperience(50);
    }

    public function testConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals('experience', StatsHandler::ExperiencePropertyName);
        $this->assertEquals('level', StatsHandler::LevelPropertyName);
        $this->assertEquals('attack', StatsHandler::AttackPropertyName);
        $this->assertEquals('defense', StatsHandler::DefensePropertyName);
    }

    public function testRequiredExperienceAlwaysIncreases(): void
    {
        $levels = range(1, 15);
        $this->character->method(PropertyHook::get("level"))->willReturn(...$levels);

        $required = 0;
        foreach ($levels as $level) {
            $requiredExperience = $this->stats->getRequiredExperience();
            $this->assertGreaterThan($required, $requiredExperience);
            $required = $requiredExperience;
        }
    }

    public function testRequiredExperienceIsNullAboveMaxLevel(): void
    {
        $this->character->method(PropertyHook::get("level"))->willReturn(16);
        $this->assertNull($this->stats->getRequiredExperience());
    }
}