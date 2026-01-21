<?php

namespace LotGD2\Tests\Entity\Character;

use LotGD2\Entity\Character\EquipmentItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ValueError as ValueErrorAlias;

#[CoversClass(EquipmentItem::class)]
class EquipmentItemTest extends TestCase
{
    private EquipmentItem $equipmentItem;

    protected function setUp(): void
    {
        $this->equipmentItem = new EquipmentItem(
            name: 'Iron Sword',
            strength: 10,
            value: 100
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $equipmentItem = new EquipmentItem(
            name: 'Steel Shield',
            strength: 15,
            value: 250
        );

        $this->assertSame('Steel Shield', $equipmentItem->getName());
        $this->assertSame(15, $equipmentItem->getStrength());
        $this->assertSame(250, $equipmentItem->getValue());
    }

    public function testGetName(): void
    {
        $this->assertSame('Iron Sword', $this->equipmentItem->getName());
    }

    public function testGetStrength(): void
    {
        $this->assertSame(10, $this->equipmentItem->getStrength());
    }

    public function testGetValue(): void
    {
        $this->assertSame(100, $this->equipmentItem->getValue());
    }

    public function testConstructorWithZeroStrength(): void
    {
        $equipmentItem = new EquipmentItem(
            name: 'Broken Dagger',
            strength: 0,
            value: 10
        );

        $this->assertSame(0, $equipmentItem->getStrength());
    }

    public function testConstructorWithNegativeValue(): void
    {
        $equipmentItem = new EquipmentItem(
            name: 'Cursed Ring',
            strength: 5,
            value: -50
        );

        $this->assertSame(-50, $equipmentItem->getValue());
    }

    public function testConstructorWithEmptyName(): void
    {
        $this->expectException(ValueErrorAlias::class);

        $equipmentItem = new EquipmentItem(
            name: '',
            strength: 5,
            value: 50
        );
    }

    public function testConstructorWithSpecialCharactersInName(): void
    {
        $name = 'Sword of "Doom" & Destruction!';
        $equipmentItem = new EquipmentItem(
            name: $name,
            strength: 20,
            value: 500
        );

        $this->assertSame($name, $equipmentItem->getName());
    }

    public function testMultipleInstances(): void
    {
        $sword = new EquipmentItem(name: 'Sword', strength: 10, value: 100);
        $shield = new EquipmentItem(name: 'Shield', strength: 5, value: 150);

        $this->assertSame('Sword', $sword->getName());
        $this->assertSame('Shield', $shield->getName());
        $this->assertSame(10, $sword->getStrength());
        $this->assertSame(5, $shield->getStrength());
        $this->assertNotSame($sword, $shield);
    }
}