<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Stats;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Stats::class)]
class StatsTest extends TestCase
{
    private Stats $stats;
    private Character $character;
    private Equipment $equipment;
    private ?LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->character = $this->createMock(Character::class);
        $this->equipment = $this->createMock(Equipment::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->stats = new Stats(
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
            ->with(Stats::ExperiencePropertyName, 0)
            ->willReturn(0);

        $this->assertEquals(0, $this->stats->getExperience());
    }

    public function testGetExperienceReturnsSetValue(): void
    {
        $expectedExperience = 1000;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::ExperiencePropertyName, 0)
            ->willReturn($expectedExperience);

        $this->assertEquals($expectedExperience, $this->stats->getExperience());
    }

    public function testSetExperienceCallsCharacterSetProperty(): void
    {
        $experience = 500;
        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(Stats::ExperiencePropertyName, $experience);

        $result = $this->stats->setExperience($experience);

        $this->assertSame($this->stats, $result);
    }

    public function testSetExperienceReturnsStaticForChaining(): void
    {
        $this->character
            ->expects($this->once())
            ->method('setProperty');

        $result = $this->stats->setExperience(100);

        $this->assertInstanceOf(Stats::class, $result);
    }

    public function testAddExperienceAddsToCurrentValue(): void
    {
        $currentExperience = 100;
        $addedExperience = 50;
        $expectedTotal = 150;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::ExperiencePropertyName, 0)
            ->willReturn($currentExperience);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(Stats::ExperiencePropertyName, $expectedTotal);

        $result = $this->stats->addExperience($addedExperience);

        $this->assertSame($this->stats, $result);
    }

    public function testAddExperienceWithZeroCurrentValue(): void
    {
        $addedExperience = 100;

        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::ExperiencePropertyName, 0)
            ->willReturn(0);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(Stats::ExperiencePropertyName, $addedExperience);

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

        $this->assertInstanceOf(Stats::class, $result);
    }

    public function testGetLevelCallsCharacterGetLevel(): void
    {
        $expectedLevel = 5;
        $this->character
            ->expects($this->once())
            ->method('getLevel')
            ->willReturn($expectedLevel);

        $this->assertEquals($expectedLevel, $this->stats->getLevel());
    }

    public function testGetAttackReturnsDefaultValueOne(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::AttackPropertyName, 1)
            ->willReturn(1);

        $this->assertEquals(1, $this->stats->getAttack());
    }

    public function testGetAttackReturnsSetValue(): void
    {
        $expectedAttack = 10;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::AttackPropertyName, 1)
            ->willReturn($expectedAttack);

        $this->assertEquals($expectedAttack, $this->stats->getAttack());
    }

    public function testGetDefenseReturnsDefaultValueOne(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::DefensePropertyName, 1)
            ->willReturn(1);

        $this->assertEquals(1, $this->stats->getDefense());
    }

    public function testGetDefenseReturnsSetValue(): void
    {
        $expectedDefense = 8;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::DefensePropertyName, 1)
            ->willReturn($expectedDefense);

        $this->assertEquals($expectedDefense, $this->stats->getDefense());
    }

    public function testGetTotalAttackWithNoWeapon(): void
    {
        $baseAttack = 5;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::AttackPropertyName, 1)
            ->willReturn($baseAttack);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(Equipment::WeaponSlot)
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
            ->with(Stats::AttackPropertyName, 1)
            ->willReturn($baseAttack);

        $weapon = $this->createMock(EquipmentItem::class);
        $weapon
            ->expects($this->once())
            ->method('getValue')
            ->willReturn($weaponValue);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(Equipment::WeaponSlot)
            ->willReturn($weapon);

        $this->assertEquals($expectedTotal, $this->stats->getTotalAttack());
    }

    public function testGetTotalDefenseWithNoArmor(): void
    {
        $baseDefense = 3;
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::DefensePropertyName, 1)
            ->willReturn($baseDefense);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(Equipment::ArmorSlot)
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
            ->with(Stats::DefensePropertyName, 1)
            ->willReturn($baseDefense);

        $armor = $this->createMock(EquipmentItem::class);
        $armor
            ->expects($this->once())
            ->method('getValue')
            ->willReturn($armorValue);

        $this->equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(Equipment::ArmorSlot)
            ->willReturn($armor);

        $this->assertEquals($expectedTotal, $this->stats->getTotalDefense());
    }

    public function testMultipleExperienceAdditions(): void
    {
        $this->character
            ->expects($this->once())
            ->method('getProperty')
            ->with(Stats::ExperiencePropertyName, 0)
            ->willReturn(100);

        $this->character
            ->expects($this->once())
            ->method('setProperty')
            ->with(Stats::ExperiencePropertyName, 150);

        $this->stats->addExperience(50);
    }

    public function testConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals('experience', Stats::ExperiencePropertyName);
        $this->assertEquals('level', Stats::LevelPropertyName);
        $this->assertEquals('attack', Stats::AttackPropertyName);
        $this->assertEquals('defense', Stats::DefensePropertyName);
    }
}