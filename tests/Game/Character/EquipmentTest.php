<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Equipment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Equipment::class)]
#[UsesClass(Character::class)]
#[UsesClass(EquipmentItem::class)]
class EquipmentTest extends TestCase
{
    public function testGetEquipment(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Sword", 15, 500);
        $armor = new EquipmentItem("Armor", 10, 300);

        $character->setProperties([
            Equipment::PropertyName => [
                Equipment::WeaponSlot => $weapon,
                Equipment::ArmorSlot => $armor,
            ],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame($weapon, $equipment->getItemInSlot(Equipment::WeaponSlot));
        $this->assertSame($armor, $equipment->getItemInSlot(Equipment::ArmorSlot));
    }

    public function testInitialSetEquipment(): void
    {
        $character = new Character();
        $initialWeapon = new EquipmentItem("Sword", 15, 500);
        $initialArmor = new EquipmentItem("Armor", 10, 300);

        $character->setProperties([
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method("debug");

        $equipment = new Equipment($loggerMock, $character);

        $equipment->setItemInSlot(Equipment::ArmorSlot, $initialArmor);
        $equipment->setItemInSlot(Equipment::WeaponSlot, $initialWeapon);

        $this->assertSame($initialWeapon, $equipment->getItemInSlot(Equipment::WeaponSlot));
        $this->assertSame($initialArmor, $equipment->getItemInSlot(Equipment::ArmorSlot));
    }

    public function testSetEquipment(): void
    {
        $character = new Character();
        $initialWeapon = new EquipmentItem("Sword", 15, 500);
        $initialArmor = new EquipmentItem("Armor", 10, 300);
        $newWeapon = new EquipmentItem("Legendary Weapon", 9001, 1_000_000);
        $newArmor = new EquipmentItem("Uber Armor", 3005, 602_230);

        $character->setProperties([
            Equipment::PropertyName => [
                Equipment::WeaponSlot => $initialWeapon,
                Equipment::ArmorSlot => $initialArmor,
            ],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method("debug");

        $equipment = new Equipment($loggerMock, $character);

        $equipment->setItemInSlot(Equipment::ArmorSlot, $newArmor);
        $equipment->setItemInSlot(Equipment::WeaponSlot, $newWeapon);

        $this->assertNotSame($initialWeapon, $equipment->getItemInSlot(Equipment::WeaponSlot));
        $this->assertNotSame($initialArmor, $equipment->getItemInSlot(Equipment::ArmorSlot));
        $this->assertSame($newWeapon, $equipment->getItemInSlot(Equipment::WeaponSlot));
        $this->assertSame($newArmor, $equipment->getItemInSlot(Equipment::ArmorSlot));
    }

    public function testGetEmptyNameForWeaponSlot(): void
    {
        $character = new Character();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Fists", $equipment->getEmptyName(Equipment::WeaponSlot));
    }

    public function testGetEmptyNameForArmorSlot(): void
    {
        $character = new Character();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("T-Shirt", $equipment->getEmptyName(Equipment::ArmorSlot));
    }

    public function testGetEmptyNameForUnknownSlot(): void
    {
        $character = new Character();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Nothing", $equipment->getEmptyName("unknown-slot"));
        $this->assertSame("Nothing", $equipment->getEmptyName("ring"));
        $this->assertSame("Nothing", $equipment->getEmptyName(""));
    }

    public function testGetNameWithEquippedWeapon(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Iron Sword", 15, 500);

        $character->setProperties([
            Equipment::PropertyName => [
                Equipment::WeaponSlot => $weapon,
            ],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Iron Sword", $equipment->getName(Equipment::WeaponSlot));
    }

    public function testGetNameWithEquippedArmor(): void
    {
        $character = new Character();
        $armor = new EquipmentItem("Chain Mail", 10, 300);

        $character->setProperties([
            Equipment::PropertyName => [
                Equipment::ArmorSlot => $armor,
            ],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Chain Mail", $equipment->getName(Equipment::ArmorSlot));
    }

    public function testGetNameWithoutEquippedWeapon(): void
    {
        $character = new Character();
        $character->setProperties([]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Fists", $equipment->getName(Equipment::WeaponSlot));
    }

    public function testGetNameWithoutEquippedArmor(): void
    {
        $character = new Character();
        $character->setProperties([]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("T-Shirt", $equipment->getName(Equipment::ArmorSlot));
    }

    public function testGetNameForUnknownSlot(): void
    {
        $character = new Character();
        $character->setProperties([]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Nothing", $equipment->getName("unknown-slot"));
        $this->assertSame("Nothing", $equipment->getName("helmet"));
    }

    public function testGetNameWithPartialEquipment(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Battle Axe", 20, 750);

        $character->setProperties([
            Equipment::PropertyName => [
                Equipment::WeaponSlot => $weapon,
                // No armor equipped
            ],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Battle Axe", $equipment->getName(Equipment::WeaponSlot));
        $this->assertSame("T-Shirt", $equipment->getName(Equipment::ArmorSlot));
    }

    public function testGetNameWithEmptyEquipmentProperty(): void
    {
        $character = new Character();
        $character->setProperties([
            Equipment::PropertyName => [],
        ]);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new Equipment($loggerMock, $character);

        $this->assertSame("Fists", $equipment->getName(Equipment::WeaponSlot));
        $this->assertSame("T-Shirt", $equipment->getName(Equipment::ArmorSlot));
    }
}