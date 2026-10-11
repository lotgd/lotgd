<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Handler\EquipmentHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(EquipmentHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(EquipmentItem::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[AllowMockObjectsWithoutExpectations]
class EquipmentTest extends TestCase
{
    public function testGetEquipment(): void
    {
        $character = new Character();
        $weapon = $this->createStub(EquipmentItem::class);
        $armor = $this->createStub(EquipmentItem::class);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::WeaponSlot => $weapon,
                EquipmentHandler::ArmorSlot => $armor,
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);

        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame($weapon, $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot));
        $this->assertSame($armor, $equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot));
    }

    public function testInitialSetEquipment(): void
    {
        $character = new Character();
        $initialWeapon = $this->createStub(EquipmentItem::class);
        $initialArmor = $this->createStub(EquipmentItem::class);

        $character->properties = [];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method("debug");

        $equipment = new EquipmentHandler($loggerMock);

        $equipment->setItemInSlot($character, EquipmentHandler::ArmorSlot, $initialArmor);
        $equipment->setItemInSlot($character, EquipmentHandler::WeaponSlot, $initialWeapon);

        $this->assertSame($initialWeapon, $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot));
        $this->assertSame($initialArmor, $equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot));
    }

    public function testSetEquipment(): void
    {
        $character = new Character();
        $initialWeapon = $this->createStub(EquipmentItem::class);
        $initialArmor = $this->createStub(EquipmentItem::class);
        $newWeapon = $this->createStub(EquipmentItem::class);
        $newArmor = $this->createStub(EquipmentItem::class);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::WeaponSlot => $initialWeapon,
                EquipmentHandler::ArmorSlot => $initialArmor,
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method("debug");

        $equipment = new EquipmentHandler($loggerMock);

        $equipment->setItemInSlot($character, EquipmentHandler::ArmorSlot, $newArmor);
        $equipment->setItemInSlot($character, EquipmentHandler::WeaponSlot, $newWeapon);

        $this->assertNotSame($initialWeapon, $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot));
        $this->assertNotSame($initialArmor, $equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot));
        $this->assertSame($newWeapon, $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot));
        $this->assertSame($newArmor, $equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot));
    }

    public function testGetEmptyNameForWeaponSlot(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Fists", $equipment->getEmptyName(EquipmentHandler::WeaponSlot));
    }

    public function testGetEmptyNameForArmorSlot(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("T-Shirt", $equipment->getEmptyName(EquipmentHandler::ArmorSlot));
    }

    public function testGetEmptyNameForUnknownSlot(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Nothing", $equipment->getEmptyName("unknown-slot"));
        $this->assertSame("Nothing", $equipment->getEmptyName("ring"));
        $this->assertSame("Nothing", $equipment->getEmptyName(""));
    }

    public function testGetNameWithEquippedWeapon(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Iron Sword", 15, 500);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::WeaponSlot => $weapon,
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Iron Sword", $equipment->getName($character, EquipmentHandler::WeaponSlot));
    }

    public function testGetNameWithEquippedArmor(): void
    {
        $character = new Character();
        $armor = new EquipmentItem("Chain Mail", 10, 300);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::ArmorSlot => $armor,
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Chain Mail", $equipment->getName($character, EquipmentHandler::ArmorSlot));
    }

    public function testGetNameWithoutEquippedWeapon(): void
    {
        $character = new Character();
        $character->properties = [];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Fists", $equipment->getName($character, EquipmentHandler::WeaponSlot));
    }

    public function testGetNameWithoutEquippedArmor(): void
    {
        $character = new Character();
        $character->properties = [];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("T-Shirt", $equipment->getName($character, EquipmentHandler::ArmorSlot));
    }

    public function testGetNameForUnknownSlot(): void
    {
        $character = new Character();
        $character->properties = [];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Nothing", $equipment->getName($character, "unknown-slot"));
        $this->assertSame("Nothing", $equipment->getName($character, "helmet"));
    }

    public function testGetNameWithPartialEquipment(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Battle Axe", 20, 750);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::WeaponSlot => $weapon,
                // No armor equipped
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Battle Axe", $equipment->getName($character, EquipmentHandler::WeaponSlot));
        $this->assertSame("T-Shirt", $equipment->getName($character, EquipmentHandler::ArmorSlot));
    }

    public function testGetNameWithEmptyEquipmentProperty(): void
    {
        $character = new Character();
        $character->properties = [
            EquipmentHandler::PropertyName => [],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $this->assertSame("Fists", $equipment->getName($character, EquipmentHandler::WeaponSlot));
        $this->assertSame("T-Shirt", $equipment->getName($character, EquipmentHandler::ArmorSlot));
    }

    public function testExplicitCharacterParameterInMethods(): void
    {
        $defaultCharacter = new Character();
        $customCharacter = new Character();
        $weapon = new EquipmentItem("Broadsword", 25, 1000);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $equipment->setItemInSlot($customCharacter, EquipmentHandler::WeaponSlot, $weapon);

        $this->assertNull($equipment->getItemInSlot($defaultCharacter, EquipmentHandler::WeaponSlot));
        $this->assertSame($weapon, $equipment->getItemInSlot($customCharacter, EquipmentHandler::WeaponSlot));
        $this->assertSame("Broadsword", $equipment->getName($customCharacter, EquipmentHandler::WeaponSlot));
        $this->assertSame("Fists", $equipment->getName($defaultCharacter, EquipmentHandler::WeaponSlot));
    }

    public function testOnCharacterResetResetsWeaponAndArmor(): void
    {
        $character = new Character();
        $weapon = new EquipmentItem("Dragon Slayer", 100, 50000);
        $armor = new EquipmentItem("Dragon Scale Armor", 80, 40000);

        $character->properties = [
            EquipmentHandler::PropertyName => [
                EquipmentHandler::WeaponSlot => $weapon,
                EquipmentHandler::ArmorSlot => $armor,
            ],
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $equipment = new EquipmentHandler($loggerMock);

        $event = new CharacterChangeEvent($character, $character);
        $equipment->onCharacterReset($event);

        $resetWeapon = $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot);
        $resetArmor = $equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot);

        $this->assertNotNull($resetWeapon);
        $this->assertSame("Fists", $resetWeapon->getName());
        $this->assertSame(0, $resetWeapon->getStrength());
        $this->assertSame(0, $resetWeapon->getValue());

        $this->assertNotNull($resetArmor);
        $this->assertSame("T-Shirt", $resetArmor->getName());
        $this->assertSame(0, $resetArmor->getStrength());
        $this->assertSame(0, $resetArmor->getValue());
    }
}