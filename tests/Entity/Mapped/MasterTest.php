<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Master;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Master::class)]
class MasterTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $master = new Master();

        $this->assertInstanceOf(Master::class, $master);
        $this->assertNull($master->id);
        $this->assertNull($master->name);
        $this->assertNull($master->level);
        $this->assertNull($master->weapon);
        $this->assertNull($master->health);
        $this->assertNull($master->attack);
        $this->assertNull($master->defense);
        $this->assertNull($master->textDefeated);
        $this->assertNull($master->textLost);
    }

    public function testConstructorWithAllParameters()
    {
        $master = new Master(
            name: "Grand Master Warrior",
            level: 100,
            weapon: "Legendary Blade",
            health: 500,
            attack: 80,
            defense: 75,
            textDefeated: "The grand master bows in respect, acknowledging your superior skill.",
            textLost: "You are no match for the grand master's centuries of experience."
        );

        $this->assertSame("Grand Master Warrior", $master->name);
        $this->assertSame(100, $master->level);
        $this->assertSame("Legendary Blade", $master->weapon);
        $this->assertSame(500, $master->health);
        $this->assertSame(80, $master->attack);
        $this->assertSame(75, $master->defense);
        $this->assertSame("The grand master bows in respect, acknowledging your superior skill.", $master->textDefeated);
        $this->assertSame("You are no match for the grand master's centuries of experience.", $master->textLost);
    }

    public function testIdProperty()
    {
        $master = new Master();
        $this->assertNull($master->id);

        // Note: ID is private(set), so it can only be set internally by Doctrine ORM
        // We cannot test setting it directly as it would be a compile error
    }

    public function testNameProperty()
    {
        $master = new Master();

        // Test setter
        $master->name = "Master of Swords";
        $this->assertSame("Master of Swords", $master->name);

        // Test constructor assignment
        $master2 = new Master(name: "Master of Magic");
        $this->assertSame("Master of Magic", $master2->name);

        // Test null assignment
        $master->name = null;
        $this->assertNull($master->name);

        // Test empty string
        $master->name = "";
        $this->assertSame("", $master->name);

        // Test long name
        $longName = "Master of the Ancient Arts of Combat and Wisdom";
        $master->name = $longName;
        $this->assertSame($longName, $master->name);
    }

    public function testLevelProperty()
    {
        $master = new Master();

        // Test setter with valid values
        $master->level = 1;
        $this->assertSame(1, $master->level);

        $master->level = 255;
        $this->assertSame(255, $master->level);

        // Test constructor assignment
        $master2 = new Master(level: 50);
        $this->assertSame(50, $master2->level);

        // Test null assignment
        $master->level = null;
        $this->assertNull($master->level);

        // Test boundary values
        $master->level = 1;
        $this->assertSame(1, $master->level);

        $master->level = 255;
        $this->assertSame(255, $master->level);

        // Test typical master levels
        $master->level = 25;
        $this->assertSame(25, $master->level);

        $master->level = 100;
        $this->assertSame(100, $master->level);
    }

    public function testWeaponProperty()
    {
        $master = new Master();

        // Test setter
        $master->weapon = "Ancient Staff";
        $this->assertSame("Ancient Staff", $master->weapon);

        // Test constructor assignment
        $master2 = new Master(weapon: "Master's Blade");
        $this->assertSame("Master's Blade", $master2->weapon);

        // Test null assignment
        $master->weapon = null;
        $this->assertNull($master->weapon);

        // Test empty string
        $master->weapon = "";
        $this->assertSame("", $master->weapon);

        // Test various weapon types
        $weapons = [
            "Enchanted Sword",
            "Staff of Power",
            "Martial Arts Techniques",
            "Bow of Precision",
            "Mystic Orb"
        ];

        foreach ($weapons as $weapon) {
            $master->weapon = $weapon;
            $this->assertSame($weapon, $master->weapon);
        }
    }

    public function testHealthProperty()
    {
        $master = new Master();

        // Test setter
        $master->health = 300;
        $this->assertSame(300, $master->health);

        // Test constructor assignment
        $master2 = new Master(health: 450);
        $this->assertSame(450, $master2->health);

        // Test null assignment
        $master->health = null;
        $this->assertNull($master->health);

        // Test zero and negative values
        $master->health = 0;
        $this->assertSame(0, $master->health);

        $master->health = -10;
        $this->assertSame(-10, $master->health);

        // Test high health values for masters
        $master->health = 1000;
        $this->assertSame(1000, $master->health);
    }

    public function testAttackProperty()
    {
        $master = new Master();

        // Test setter
        $master->attack = 65;
        $this->assertSame(65, $master->attack);

        // Test constructor assignment
        $master2 = new Master(attack: 90);
        $this->assertSame(90, $master2->attack);

        // Test null assignment
        $master->attack = null;
        $this->assertNull($master->attack);

        // Test zero and negative values
        $master->attack = 0;
        $this->assertSame(0, $master->attack);

        $master->attack = -5;
        $this->assertSame(-5, $master->attack);

        // Test high attack values typical for masters
        $master->attack = 100;
        $this->assertSame(100, $master->attack);
    }

    public function testDefenseProperty()
    {
        $master = new Master();

        // Test setter
        $master->defense = 55;
        $this->assertSame(55, $master->defense);

        // Test constructor assignment
        $master2 = new Master(defense: 70);
        $this->assertSame(70, $master2->defense);

        // Test null assignment
        $master->defense = null;
        $this->assertNull($master->defense);

        // Test zero and negative values
        $master->defense = 0;
        $this->assertSame(0, $master->defense);

        $master->defense = -3;
        $this->assertSame(-3, $master->defense);

        // Test high defense values typical for masters
        $master->defense = 85;
        $this->assertSame(85, $master->defense);
    }

    public function testTextDefeatedProperty()
    {
        $master = new Master();

        // Test setter
        $defeatedText = "The master nods with approval as you prove your worth.";
        $master->textDefeated = $defeatedText;
        $this->assertSame($defeatedText, $master->textDefeated);

        // Test constructor assignment
        $constructorText = "You have surpassed your teacher.";
        $master2 = new Master(textDefeated: $constructorText);
        $this->assertSame($constructorText, $master2->textDefeated);

        // Test null assignment
        $master->textDefeated = null;
        $this->assertNull($master->textDefeated);

        // Test empty string
        $master->textDefeated = "";
        $this->assertSame("", $master->textDefeated);

        // Test various defeat messages
        $messages = [
            "The master smiles proudly at your achievement.",
            "You have learned well, young one.",
            "The student has become the master.",
            "Your training is complete."
        ];

        foreach ($messages as $message) {
            $master->textDefeated = $message;
            $this->assertSame($message, $master->textDefeated);
        }
    }

    public function testTextLostProperty()
    {
        $master = new Master();

        // Test setter
        $lostText = "You still have much to learn, continue your training.";
        $master->textLost = $lostText;
        $this->assertSame($lostText, $master->textLost);

        // Test constructor assignment
        $constructorText = "Return when you are stronger.";
        $master2 = new Master(textLost: $constructorText);
        $this->assertSame($constructorText, $master2->textLost);

        // Test null assignment (this property is nullable)
        $master->textLost = null;
        $this->assertNull($master->textLost);

        // Test empty string
        $master->textLost = "";
        $this->assertSame("", $master->textLost);

        // Test various loss messages
        $messages = [
            "You are not yet ready to face me.",
            "Practice more and return when you are stronger.",
            "Your skills need refinement.",
            "The path to mastery is long and difficult."
        ];

        foreach ($messages as $message) {
            $master->textLost = $message;
            $this->assertSame($message, $master->textLost);
        }
    }

    public function testCompleteMasterScenario()
    {
        // Test creating a complete master and modifying its properties
        $master = new Master(
            name: "Elder Master Kai",
            level: 75,
            weapon: "Ancient Katana",
            health: 400,
            attack: 70,
            defense: 65,
            textDefeated: "You have proven yourself worthy, young warrior.",
            textLost: "Your spirit is strong, but your technique needs work."
        );

        // Verify initial state
        $this->assertSame("Elder Master Kai", $master->name);
        $this->assertSame(75, $master->level);
        $this->assertSame("Ancient Katana", $master->weapon);
        $this->assertSame(400, $master->health);
        $this->assertSame(70, $master->attack);
        $this->assertSame(65, $master->defense);
        $this->assertSame("You have proven yourself worthy, young warrior.", $master->textDefeated);
        $this->assertSame("Your spirit is strong, but your technique needs work.", $master->textLost);

        // Test modifying properties during combat
        $master->health = 350; // Master takes some damage
        $master->attack = 75; // Master becomes more focused
        $master->defense = 70; // Master improves defensive stance

        $this->assertSame(350, $master->health);
        $this->assertSame(75, $master->attack);
        $this->assertSame(70, $master->defense);

        // Other properties should remain unchanged
        $this->assertSame("Elder Master Kai", $master->name);
        $this->assertSame(75, $master->level);
        $this->assertSame("Ancient Katana", $master->weapon);
        $this->assertSame("You have proven yourself worthy, young warrior.", $master->textDefeated);
        $this->assertSame("Your spirit is strong, but your technique needs work.", $master->textLost);
    }

    public function testMinimalMasterConfiguration()
    {
        // Test creating a master with minimal required properties
        $master = new Master(
            name: "Novice Master",
            level: 10,
            weapon: "Training Sword",
            health: 80,
            attack: 15,
            defense: 12,
            textDefeated: "Well done, student."
        );

        $this->assertSame("Novice Master", $master->name);
        $this->assertSame(10, $master->level);
        $this->assertSame("Training Sword", $master->weapon);
        $this->assertSame(80, $master->health);
        $this->assertSame(15, $master->attack);
        $this->assertSame(12, $master->defense);
        $this->assertSame("Well done, student.", $master->textDefeated);

        // Optional textLost should be null
        $this->assertNull($master->textLost);
    }

    public function testHighLevelMasterScenario()
    {
        // Test a maximum level master
        $master = new Master(
            name: "Grandmaster Supreme",
            level: 255,
            weapon: "Sword of Ultimate Power",
            health: 2000,
            attack: 150,
            defense: 120,
            textDefeated: "You have achieved the impossible. You are now ready to become a teacher yourself.",
            textLost: "Even the greatest warriors cannot defeat me easily. Your potential is immense, but you need more experience."
        );

        $this->assertSame("Grandmaster Supreme", $master->name);
        $this->assertSame(255, $master->level);
        $this->assertSame("Sword of Ultimate Power", $master->weapon);
        $this->assertSame(2000, $master->health);
        $this->assertSame(150, $master->attack);
        $this->assertSame(120, $master->defense);
        $this->assertSame("You have achieved the impossible. You are now ready to become a teacher yourself.", $master->textDefeated);
        $this->assertSame("Even the greatest warriors cannot defeat me easily. Your potential is immense, but you need more experience.", $master->textLost);
    }

    public function testPropertyModificationChaining()
    {
        $master = new Master();

        // Test that we can modify multiple properties in sequence
        $master->name = "Test Master";
        $master->level = 42;
        $master->weapon = "Test Weapon";
        $master->health = 200;
        $master->attack = 35;
        $master->defense = 30;
        $master->textDefeated = "Test defeated text";
        $master->textLost = "Test lost text";

        // Verify all properties are set correctly
        $this->assertSame("Test Master", $master->name);
        $this->assertSame(42, $master->level);
        $this->assertSame("Test Weapon", $master->weapon);
        $this->assertSame(200, $master->health);
        $this->assertSame(35, $master->attack);
        $this->assertSame(30, $master->defense);
        $this->assertSame("Test defeated text", $master->textDefeated);
        $this->assertSame("Test lost text", $master->textLost);
    }

    public function testDifferentMasterTypes()
    {
        // Test creating different types of masters
        $swordMaster = new Master(
            name: "Blade Master",
            level: 50,
            weapon: "Masterwork Sword",
            health: 300,
            attack: 80,
            defense: 60,
            textDefeated: "Your swordsmanship has surpassed mine.",
            textLost: "Your blade work needs improvement."
        );

        $magicMaster = new Master(
            name: "Archmage",
            level: 60,
            weapon: "Staff of Arcane Mastery",
            health: 250,
            attack: 90,
            defense: 50,
            textDefeated: "Your magical prowess is truly impressive.",
            textLost: "Your understanding of magic is still incomplete."
        );

        $martialMaster = new Master(
            name: "Martial Arts Master",
            level: 45,
            weapon: "Bare Hands",
            health: 350,
            attack: 75,
            defense: 80,
            textDefeated: "You have mastered the way of the fist.",
            textLost: "Your body and mind must work as one."
        );

        // Verify each master type
        $this->assertSame("Blade Master", $swordMaster->name);
        $this->assertSame("Masterwork Sword", $swordMaster->weapon);
        $this->assertSame(80, $swordMaster->attack);

        $this->assertSame("Archmage", $magicMaster->name);
        $this->assertSame("Staff of Arcane Mastery", $magicMaster->weapon);
        $this->assertSame(90, $magicMaster->attack);

        $this->assertSame("Martial Arts Master", $martialMaster->name);
        $this->assertSame("Bare Hands", $martialMaster->weapon);
        $this->assertSame(80, $martialMaster->defense);
    }
}