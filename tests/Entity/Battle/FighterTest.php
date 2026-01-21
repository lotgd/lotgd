<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\Fighter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fighter::class)]
class FighterTest extends TestCase
{
    private Fighter $fighter;

    protected function setUp(): void
    {
        $this->fighter = new Fighter(
            name: 'Test Fighter',
            level: 5,
            weapon: 'Sword',
            health: 100,
            attack: 15,
            defense: 10,
        );
    }

    public function testConstructorInitializesPropertiesCorrectly(): void
    {
        $fighter = new Fighter(
            name: 'Hero',
            level: 10,
            weapon: 'Axe',
            health: 150,
            attack: 20,
            defense: 12,
        );

        $this->assertSame('Hero', $fighter->name);
        $this->assertSame(10, $fighter->level);
        $this->assertSame('Axe', $fighter->weapon);
        $this->assertSame(150, $fighter->health);
        $this->assertSame(20, $fighter->attack);
        $this->assertSame(12, $fighter->defense);
    }

    public function testConstructorAllowsNullValues(): void
    {
        $fighter = new Fighter(
            name: null,
            level: null,
            weapon: null,
            health: null,
            attack: null,
            defense: null,
        );

        $this->assertNull($fighter->name);
        $this->assertNull($fighter->level);
        $this->assertNull($fighter->weapon);
        $this->assertNull($fighter->health);
        $this->assertNull($fighter->attack);
        $this->assertNull($fighter->defense);
    }

    public function testNamePropertyCanBeGetAndSet(): void
    {
        $this->assertSame('Test Fighter', $this->fighter->name);
        
        $this->fighter->name = 'New Name';
        $this->assertSame('New Name', $this->fighter->name);
        
        $this->fighter->name = null;
        $this->assertNull($this->fighter->name);
    }

    public function testLevelPropertyCanBeGetAndSet(): void
    {
        $this->assertSame(5, $this->fighter->level);
        
        $this->fighter->level = 20;
        $this->assertSame(20, $this->fighter->level);
        
        $this->fighter->level = null;
        $this->assertNull($this->fighter->level);
    }

    public function testWeaponPropertyCanBeGetAndSet(): void
    {
        $this->assertSame('Sword', $this->fighter->weapon);
        
        $this->fighter->weapon = 'Dagger';
        $this->assertSame('Dagger', $this->fighter->weapon);
        
        $this->fighter->weapon = null;
        $this->assertNull($this->fighter->weapon);
    }

    public function testHealthPropertyCanBeGetAndSet(): void
    {
        $this->assertSame(100, $this->fighter->health);
        
        $this->fighter->health = 75;
        $this->assertSame(75, $this->fighter->health);
        
        $this->fighter->health = null;
        $this->assertNull($this->fighter->health);
    }

    public function testAttackPropertyCanBeGetAndSet(): void
    {
        $this->assertSame(15, $this->fighter->attack);
        
        $this->fighter->attack = 25;
        $this->assertSame(25, $this->fighter->attack);
        
        $this->fighter->attack = null;
        $this->assertNull($this->fighter->attack);
    }

    public function testDefensePropertyCanBeGetAndSet(): void
    {
        $this->assertSame(10, $this->fighter->defense);
        
        $this->fighter->defense = 18;
        $this->assertSame(18, $this->fighter->defense);
        
        $this->fighter->defense = null;
        $this->assertNull($this->fighter->defense);
    }

    public function testMaxHealthPropertyCanBeGetAndSet(): void
    {
        $this->fighter->maxHealth = 150;
        $this->assertSame(150, $this->fighter->maxHealth);
        
        $this->fighter->maxHealth = 200;
        $this->assertSame(200, $this->fighter->maxHealth);
        
        $this->fighter->maxHealth = null;
        $this->assertNull($this->fighter->maxHealth);
    }

    public function testKwargsIsInitializedAsEmptyArray(): void
    {
        $this->assertIsArray($this->fighter->kwargs);
        $this->assertEmpty($this->fighter->kwargs);
    }

    public function testKwargsPropertyCanBeGetAndSet(): void
    {
        $testData = ['key1' => 'value1', 'key2' => 42, 'nested' => ['deep' => 'value']];
        
        $this->fighter->kwargs = $testData;
        
        $this->assertSame($testData, $this->fighter->kwargs);
        $this->assertSame('value1', $this->fighter->kwargs['key1']);
        $this->assertSame(42, $this->fighter->kwargs['key2']);
        $this->assertSame('value', $this->fighter->kwargs['nested']['deep']);
    }

    public function testDamageReducesHealthValue(): void
    {
        $initialHealth = $this->fighter->health;
        
        $result = $this->fighter->damage(30);
        
        $this->assertSame($this->fighter, $result);
        $this->assertSame($initialHealth - 30, $this->fighter->health);
    }

    public function testDamageCanReduceHealthToZero(): void
    {
        $this->fighter->health = 50;
        
        $this->fighter->damage(50);
        
        $this->assertSame(0, $this->fighter->health);
    }

    public function testDamageCanReduceHealthBelowZero(): void
    {
        $this->fighter->health = 50;
        
        $this->fighter->damage(100);
        
        $this->assertSame(0, $this->fighter->health);
    }

    public function testDamageWithZeroDamageDoesNotChangeHealth(): void
    {
        $initialHealth = $this->fighter->health;
        
        $this->fighter->damage(0);
        
        $this->assertSame($initialHealth, $this->fighter->health);
    }

    public function testDamageReturnsStaticInstance(): void
    {
        $result = $this->fighter->damage(10);
        
        $this->assertInstanceOf(Fighter::class, $result);
    }

    public function testDamageIsChainable(): void
    {
        $this->fighter->health = 100;
        
        $result = $this->fighter->damage(30)->damage(20)->damage(10);
        
        $this->assertSame(40, $this->fighter->health);
        $this->assertInstanceOf(Fighter::class, $result);
    }

    public function testMultiplePropertiesCanBeSetAndRetrieved(): void
    {
        $this->fighter->name = 'Champion';
        $this->fighter->level = 99;
        $this->fighter->weapon = 'Legendary Sword';
        $this->fighter->health = 500;
        $this->fighter->attack = 100;
        $this->fighter->defense = 50;
        $this->fighter->maxHealth = 500;
        $this->fighter->kwargs = ['special' => 'ability'];

        $this->assertSame('Champion', $this->fighter->name);
        $this->assertSame(99, $this->fighter->level);
        $this->assertSame('Legendary Sword', $this->fighter->weapon);
        $this->assertSame(500, $this->fighter->health);
        $this->assertSame(100, $this->fighter->attack);
        $this->assertSame(50, $this->fighter->defense);
        $this->assertSame(500, $this->fighter->maxHealth);
        $this->assertSame(['special' => 'ability'], $this->fighter->kwargs);
    }

    public function testDifferentInstancesHaveIndependentProperties(): void
    {
        $fighter1 = new Fighter('Fighter1', 5, 'Sword', 100, 15, 10);
        $fighter2 = new Fighter('Fighter2', 8, 'Axe', 120, 18, 12);

        $this->assertSame('Fighter1', $fighter1->name);
        $this->assertSame('Fighter2', $fighter2->name);

        $fighter1->name = 'Modified Fighter1';
        $this->assertSame('Modified Fighter1', $fighter1->name);
        $this->assertSame('Fighter2', $fighter2->name);
    }
}