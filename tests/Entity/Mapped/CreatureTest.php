<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Creature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Creature::class)]
class CreatureTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $creature = new Creature();

        $this->assertInstanceOf(Creature::class, $creature);
        $this->assertNull($creature->id);
        $this->assertNull($creature->name);
        $this->assertNull($creature->level);
        $this->assertNull($creature->weapon);
        $this->assertNull($creature->health);
        $this->assertNull($creature->attack);
        $this->assertNull($creature->defense);
        $this->assertNull($creature->textDefeated);
        $this->assertNull($creature->textLost);
        $this->assertNull($creature->gold);
        $this->assertNull($creature->experience);
        $this->assertNull($creature->credits);
    }

    public function testConstructorWithAllParameters()
    {
        $creature = new Creature(
            name: "Orc Warrior",
            level: 5,
            weapon: "Rusty Sword",
            health: 100,
            attack: 15,
            defense: 10,
            textDefeated: "The orc warrior falls to the ground, defeated.",
            textLost: "You barely escape from the mighty orc!",
            gold: 50,
            experience: 75,
            credits: "Created by John Doe"
        );

        $this->assertSame("Orc Warrior", $creature->name);
        $this->assertSame(5, $creature->level);
        $this->assertSame("Rusty Sword", $creature->weapon);
        $this->assertSame(100, $creature->health);
        $this->assertSame(15, $creature->attack);
        $this->assertSame(10, $creature->defense);
        $this->assertSame("The orc warrior falls to the ground, defeated.", $creature->textDefeated);
        $this->assertSame("You barely escape from the mighty orc!", $creature->textLost);
        $this->assertSame(50, $creature->gold);
        $this->assertSame(75, $creature->experience);
        $this->assertSame("Created by John Doe", $creature->credits);
    }

    public function testIdProperty()
    {
        $creature = new Creature();
        $this->assertNull($creature->id);
        
        // Note: ID is private(set), so it can only be set internally by Doctrine ORM
        // We cannot test setting it directly as it would be a compile error
    }

    public function testNameProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->name = "Dragon";
        $this->assertSame("Dragon", $creature->name);
        
        // Test constructor assignment
        $creature2 = new Creature(name: "Goblin");
        $this->assertSame("Goblin", $creature2->name);
        
        // Test null assignment
        $creature->name = null;
        $this->assertNull($creature->name);
        
        // Test empty string
        $creature->name = "";
        $this->assertSame("", $creature->name);
    }

    public function testLevelProperty()
    {
        $creature = new Creature();
        
        // Test setter with valid values
        $creature->level = 1;
        $this->assertSame(1, $creature->level);
        
        $creature->level = 255;
        $this->assertSame(255, $creature->level);
        
        // Test constructor assignment
        $creature2 = new Creature(level: 10);
        $this->assertSame(10, $creature2->level);
        
        // Test null assignment
        $creature->level = null;
        $this->assertNull($creature->level);
        
        // Test boundary values
        $creature->level = 1;
        $this->assertSame(1, $creature->level);
        
        $creature->level = 255;
        $this->assertSame(255, $creature->level);
    }

    public function testWeaponProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->weapon = "Magic Staff";
        $this->assertSame("Magic Staff", $creature->weapon);
        
        // Test constructor assignment
        $creature2 = new Creature(weapon: "Battle Axe");
        $this->assertSame("Battle Axe", $creature2->weapon);
        
        // Test null assignment
        $creature->weapon = null;
        $this->assertNull($creature->weapon);
        
        // Test empty string
        $creature->weapon = "";
        $this->assertSame("", $creature->weapon);
    }

    public function testHealthProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->health = 150;
        $this->assertSame(150, $creature->health);
        
        // Test constructor assignment
        $creature2 = new Creature(health: 200);
        $this->assertSame(200, $creature2->health);
        
        // Test null assignment
        $creature->health = null;
        $this->assertNull($creature->health);
        
        // Test zero and negative values
        $creature->health = 0;
        $this->assertSame(0, $creature->health);
        
        $creature->health = -10;
        $this->assertSame(-10, $creature->health);
    }

    public function testAttackProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->attack = 25;
        $this->assertSame(25, $creature->attack);
        
        // Test constructor assignment
        $creature2 = new Creature(attack: 30);
        $this->assertSame(30, $creature2->attack);
        
        // Test null assignment
        $creature->attack = null;
        $this->assertNull($creature->attack);
        
        // Test zero and negative values
        $creature->attack = 0;
        $this->assertSame(0, $creature->attack);
        
        $creature->attack = -5;
        $this->assertSame(-5, $creature->attack);
    }

    public function testDefenseProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->defense = 20;
        $this->assertSame(20, $creature->defense);
        
        // Test constructor assignment
        $creature2 = new Creature(defense: 15);
        $this->assertSame(15, $creature2->defense);
        
        // Test null assignment
        $creature->defense = null;
        $this->assertNull($creature->defense);
        
        // Test zero and negative values
        $creature->defense = 0;
        $this->assertSame(0, $creature->defense);
        
        $creature->defense = -3;
        $this->assertSame(-3, $creature->defense);
    }

    public function testTextDefeatedProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $defeatedText = "The mighty beast collapses in defeat!";
        $creature->textDefeated = $defeatedText;
        $this->assertSame($defeatedText, $creature->textDefeated);
        
        // Test constructor assignment
        $constructorText = "The creature is vanquished.";
        $creature2 = new Creature(textDefeated: $constructorText);
        $this->assertSame($constructorText, $creature2->textDefeated);
        
        // Test null assignment
        $creature->textDefeated = null;
        $this->assertNull($creature->textDefeated);
        
        // Test empty string
        $creature->textDefeated = "";
        $this->assertSame("", $creature->textDefeated);
    }

    public function testTextLostProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $lostText = "The creature retreats into the shadows.";
        $creature->textLost = $lostText;
        $this->assertSame($lostText, $creature->textLost);
        
        // Test constructor assignment
        $constructorText = "You flee from the terrifying beast!";
        $creature2 = new Creature(textLost: $constructorText);
        $this->assertSame($constructorText, $creature2->textLost);
        
        // Test null assignment (this property is nullable)
        $creature->textLost = null;
        $this->assertNull($creature->textLost);
        
        // Test empty string
        $creature->textLost = "";
        $this->assertSame("", $creature->textLost);
    }

    public function testGoldProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->gold = 100;
        $this->assertSame(100, $creature->gold);
        
        // Test constructor assignment
        $creature2 = new Creature(gold: 250);
        $this->assertSame(250, $creature2->gold);
        
        // Test null assignment
        $creature->gold = null;
        $this->assertNull($creature->gold);
        
        // Test zero and negative values
        $creature->gold = 0;
        $this->assertSame(0, $creature->gold);
        
        $creature->gold = -10;
        $this->assertSame(-10, $creature->gold);
    }

    public function testExperienceProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $creature->experience = 500;
        $this->assertSame(500, $creature->experience);
        
        // Test constructor assignment
        $creature2 = new Creature(experience: 750);
        $this->assertSame(750, $creature2->experience);
        
        // Test null assignment
        $creature->experience = null;
        $this->assertNull($creature->experience);
        
        // Test zero and negative values
        $creature->experience = 0;
        $this->assertSame(0, $creature->experience);
        
        $creature->experience = -50;
        $this->assertSame(-50, $creature->experience);
    }

    public function testCreditsProperty()
    {
        $creature = new Creature();
        
        // Test setter
        $credits = "Monster design by Jane Smith";
        $creature->credits = $credits;
        $this->assertSame($credits, $creature->credits);
        
        // Test constructor assignment
        $constructorCredits = "Art by Bob Johnson";
        $creature2 = new Creature(credits: $constructorCredits);
        $this->assertSame($constructorCredits, $creature2->credits);
        
        // Test null assignment (this property is nullable)
        $creature->credits = null;
        $this->assertNull($creature->credits);
        
        // Test empty string
        $creature->credits = "";
        $this->assertSame("", $creature->credits);
    }

    public function testCompleteCreatureScenario()
    {
        // Test creating a complete creature and modifying its properties
        $creature = new Creature(
            name: "Ancient Dragon",
            level: 50,
            weapon: "Dragon Claws",
            health: 1000,
            attack: 80,
            defense: 60,
            textDefeated: "The ancient dragon lets out a final roar before collapsing.",
            textLost: "The dragon's power overwhelms you, forcing a hasty retreat.",
            gold: 500,
            experience: 2000,
            credits: "Original design by Fantasy Studios"
        );

        // Verify initial state
        $this->assertSame("Ancient Dragon", $creature->name);
        $this->assertSame(50, $creature->level);
        $this->assertSame("Dragon Claws", $creature->weapon);
        $this->assertSame(1000, $creature->health);
        $this->assertSame(80, $creature->attack);
        $this->assertSame(60, $creature->defense);
        $this->assertSame("The ancient dragon lets out a final roar before collapsing.", $creature->textDefeated);
        $this->assertSame("The dragon's power overwhelms you, forcing a hasty retreat.", $creature->textLost);
        $this->assertSame(500, $creature->gold);
        $this->assertSame(2000, $creature->experience);
        $this->assertSame("Original design by Fantasy Studios", $creature->credits);

        // Test modifying properties
        $creature->health = 800; // Creature takes damage
        $creature->attack = 85; // Gets enraged, stronger attack
        $creature->defense = 55; // Defense weakened

        $this->assertSame(800, $creature->health);
        $this->assertSame(85, $creature->attack);
        $this->assertSame(55, $creature->defense);

        // Other properties should remain unchanged
        $this->assertSame("Ancient Dragon", $creature->name);
        $this->assertSame(50, $creature->level);
        $this->assertSame("Dragon Claws", $creature->weapon);
    }

    public function testMinimalCreatureConfiguration()
    {
        // Test creating a creature with only required properties
        $creature = new Creature(
            name: "Rat",
            level: 1,
            weapon: "Teeth",
            health: 5,
            attack: 2,
            defense: 1,
            textDefeated: "The rat squeaks and dies.",
            gold: 1,
            experience: 5
        );

        $this->assertSame("Rat", $creature->name);
        $this->assertSame(1, $creature->level);
        $this->assertSame("Teeth", $creature->weapon);
        $this->assertSame(5, $creature->health);
        $this->assertSame(2, $creature->attack);
        $this->assertSame(1, $creature->defense);
        $this->assertSame("The rat squeaks and dies.", $creature->textDefeated);
        $this->assertSame(1, $creature->gold);
        $this->assertSame(5, $creature->experience);
        
        // Optional properties should be null
        $this->assertNull($creature->textLost);
        $this->assertNull($creature->credits);
    }

    public function testPropertyModificationChaining()
    {
        $creature = new Creature();
        
        // Test that we can modify multiple properties in sequence
        $creature->name = "Test Creature";
        $creature->level = 10;
        $creature->weapon = "Test Weapon";
        $creature->health = 100;
        $creature->attack = 20;
        $creature->defense = 15;
        $creature->textDefeated = "Test defeated text";
        $creature->textLost = "Test lost text";
        $creature->gold = 50;
        $creature->experience = 100;
        $creature->credits = "Test credits";
        
        // Verify all properties are set correctly
        $this->assertSame("Test Creature", $creature->name);
        $this->assertSame(10, $creature->level);
        $this->assertSame("Test Weapon", $creature->weapon);
        $this->assertSame(100, $creature->health);
        $this->assertSame(20, $creature->attack);
        $this->assertSame(15, $creature->defense);
        $this->assertSame("Test defeated text", $creature->textDefeated);
        $this->assertSame("Test lost text", $creature->textLost);
        $this->assertSame(50, $creature->gold);
        $this->assertSame(100, $creature->experience);
        $this->assertSame("Test credits", $creature->credits);
    }
}