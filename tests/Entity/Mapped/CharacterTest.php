<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Character::class)]
class CharacterTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $character = new Character();

        $this->assertInstanceOf(Character::class, $character);
        $this->assertNull($character->id);
        $this->assertNull($character->name);
        $this->assertNull($character->title);
        $this->assertNull($character->suffix);
        $this->assertNull($character->level);
        $this->assertNull($character->stage);
        $this->assertEquals([], $character->properties);
    }

    public function testConstructorWithAllParameters()
    {
        $stage = $this->createMock(Stage::class);
        $properties = ['key' => 'value', 'health' => 100];

        $character = new Character(
            name: "TestCharacter",
            title: "The Brave",
            suffix: "of Legend",
            level: 10,
            stage: $stage,
            properties: $properties
        );

        $this->assertSame("TestCharacter", $character->name);
        $this->assertSame("The Brave", $character->title);
        $this->assertSame("of Legend", $character->suffix);
        $this->assertSame(10, $character->level);
        $this->assertSame($stage, $character->stage);
        $this->assertEquals($properties, $character->properties);
    }

    public function testIdProperty()
    {
        $character = new Character();
        $this->assertNull($character->id);
        
        // Note: In real usage, the ID would be set by Doctrine ORM
    }

    public function testNameProperty()
    {
        $character = new Character();
        
        // Test setter
        $character->name = "Hero";
        $this->assertSame("Hero", $character->name);
        
        // Test constructor assignment
        $character2 = new Character(name: "Villain");
        $this->assertSame("Villain", $character2->name);
        
        // Test null assignment
        $character->name = null;
        $this->assertNull($character->name);
    }

    public function testTitleProperty()
    {
        $character = new Character();
        
        // Test setter
        $character->title = "The Great";
        $this->assertSame("The Great", $character->title);
        
        // Test constructor assignment
        $character2 = new Character(title: "The Mighty");
        $this->assertSame("The Mighty", $character2->title);
        
        // Test null assignment
        $character->title = null;
        $this->assertNull($character->title);
    }

    public function testSuffixProperty()
    {
        $character = new Character();
        
        // Test setter
        $character->suffix = "of the North";
        $this->assertSame("of the North", $character->suffix);
        
        // Test constructor assignment
        $character2 = new Character(suffix: "the Bold");
        $this->assertSame("the Bold", $character2->suffix);
        
        // Test null assignment
        $character->suffix = null;
        $this->assertNull($character->suffix);
    }

    public function testLevelProperty()
    {
        $character = new Character();
        
        // Test setter
        $character->level = 5;
        $this->assertSame(5, $character->level);
        
        // Test constructor assignment
        $character2 = new Character(level: 15);
        $this->assertSame(15, $character2->level);
        
        // Test null assignment
        $character->level = null;
        $this->assertNull($character->level);
    }

    public function testStagePropertyWithNull()
    {
        $character = new Character();
        
        // Test setting to null
        $character->stage = null;
        $this->assertNull($character->stage);
    }

    public function testStagePropertyWithStageObject()
    {
        $character = new Character();
        $stage = $this->createMock(Stage::class);
        
        // Configure mock to return the character when getOwner() is called
        $stage->expects($this->once())
              ->method('getOwner')
              ->willReturn(null);
        
        // Configure mock to expect setOwner to be called with the character
        $stage->expects($this->once())
              ->method('setOwner')
              ->with($character);
        
        $character->stage = $stage;
        $this->assertSame($stage, $character->stage);
    }

    public function testStagePropertyDoesNotSetOwnerIfAlreadySet()
    {
        $character = new Character();
        $stage = $this->createMock(Stage::class);
        
        // Configure mock to return the same character when getOwner() is called
        $stage->expects($this->once())
              ->method('getOwner')
              ->willReturn($character);
        
        // setOwner should not be called since it's already set correctly
        $stage->expects($this->never())
              ->method('setOwner');
        
        $character->stage = $stage;
        $this->assertSame($stage, $character->stage);
    }

    public function testPropertiesProperty()
    {
        $character = new Character();
        
        // Test default empty array
        $this->assertEquals([], $character->properties);
        
        // Test setter
        $properties = ['health' => 100, 'mana' => 50];
        $character->properties = $properties;
        $this->assertEquals($properties, $character->properties);
        
        // Test constructor assignment
        $character2 = new Character(properties: ['strength' => 10]);
        $this->assertEquals(['strength' => 10], $character2->properties);
        
        // Test null assignment
        $character->properties = null;
        $this->assertNull($character->properties);
    }

    public function testGetProperty()
    {
        $character = new Character(properties: [
            'health' => 100,
            'mana' => 50,
            'level' => 1,
            'experience' => 0
        ]);
        
        // Test getting existing property
        $this->assertSame(100, $character->getProperty('health'));
        $this->assertSame(50, $character->getProperty('mana'));
        $this->assertSame(1, $character->getProperty('level'));
        $this->assertSame(0, $character->getProperty('experience'));
        
        // Test getting non-existing property with default
        $this->assertNull($character->getProperty('nonexistent'));
        $this->assertSame('default', $character->getProperty('nonexistent', 'default'));
        $this->assertSame(0, $character->getProperty('missing', 0));
        
        // Test with different data types
        $character->properties = [
            'string' => 'test',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'array' => ['nested' => 'value']
        ];
        
        $this->assertSame('test', $character->getProperty('string'));
        $this->assertSame(42, $character->getProperty('int'));
        $this->assertSame(3.14, $character->getProperty('float'));
        $this->assertTrue($character->getProperty('bool'));
        $this->assertEquals(['nested' => 'value'], $character->getProperty('array'));
    }

    public function testGetPropertyWithNullProperties()
    {
        $character = new Character(properties: null);
        
        $this->assertNull($character->getProperty('anything'));
        $this->assertSame('default', $character->getProperty('anything', 'default'));
    }

    public function testSetProperty()
    {
        $character = new Character();
        
        // Test setting property on empty properties array
        $result = $character->setProperty('health', 100);
        
        $this->assertSame($character, $result); // Test fluent interface
        $this->assertSame(100, $character->getProperty('health'));
        
        // Test setting multiple properties
        $character->setProperty('mana', 50)
                 ->setProperty('level', 1)
                 ->setProperty('experience', 0);
        
        $this->assertSame(50, $character->getProperty('mana'));
        $this->assertSame(1, $character->getProperty('level'));
        $this->assertSame(0, $character->getProperty('experience'));
        
        // Test overwriting existing property
        $character->setProperty('health', 150);
        $this->assertSame(150, $character->getProperty('health'));
        
        // Test setting different data types
        $character->setProperty('string', 'test')
                 ->setProperty('int', 42)
                 ->setProperty('float', 3.14)
                 ->setProperty('bool', true)
                 ->setProperty('null', null)
                 ->setProperty('array', ['nested' => 'value']);
        
        $this->assertSame('test', $character->getProperty('string'));
        $this->assertSame(42, $character->getProperty('int'));
        $this->assertSame(3.14, $character->getProperty('float'));
        $this->assertTrue($character->getProperty('bool'));
        $this->assertNull($character->getProperty('null'));
        $this->assertEquals(['nested' => 'value'], $character->getProperty('array'));
    }

    public function testSetPropertyWithNullProperties()
    {
        $character = new Character(properties: null);
        
        $character->setProperty('test', 'value');
        
        // Should initialize properties array and set the value
        $this->assertSame('value', $character->getProperty('test'));
        $this->assertEquals(['test' => 'value'], $character->properties);
    }

    public function testSetPropertyPreservesExistingProperties()
    {
        $character = new Character(properties: [
            'existing1' => 'value1',
            'existing2' => 'value2'
        ]);
        
        $character->setProperty('new', 'newvalue');
        
        // Should preserve existing properties and add new one
        $this->assertSame('value1', $character->getProperty('existing1'));
        $this->assertSame('value2', $character->getProperty('existing2'));
        $this->assertSame('newvalue', $character->getProperty('new'));
    }

    public function testComplexScenario()
    {
        // Test a more complex scenario combining all functionality
        $stage = $this->createMock(Stage::class);
        $stage->method('getOwner')->willReturn(null);
        $stage->expects($this->once())->method('setOwner');
        
        $character = new Character(
            name: "Aragorn",
            title: "Ranger",
            suffix: "of the North",
            level: 20,
            properties: [
                'health' => 200,
                'mana' => 100,
                'strength' => 18,
                'dexterity' => 16
            ]
        );
        
        $character->stage = $stage;
        
        // Test initial state
        $this->assertSame("Aragorn", $character->name);
        $this->assertSame("Ranger", $character->title);
        $this->assertSame("of the North", $character->suffix);
        $this->assertSame(20, $character->level);
        $this->assertSame($stage, $character->stage);
        
        // Test properties
        $this->assertSame(200, $character->getProperty('health'));
        $this->assertSame(100, $character->getProperty('mana'));
        $this->assertSame(18, $character->getProperty('strength'));
        $this->assertSame(16, $character->getProperty('dexterity'));
        
        // Modify properties
        $character->setProperty('health', 180)
                 ->setProperty('experience', 5000)
                 ->setProperty('equipment', ['sword' => 'Sting']);
        
        $this->assertSame(180, $character->getProperty('health'));
        $this->assertSame(5000, $character->getProperty('experience'));
        $this->assertEquals(['sword' => 'Sting'], $character->getProperty('equipment'));
        
        // Original properties should still be there
        $this->assertSame(100, $character->getProperty('mana'));
        $this->assertSame(18, $character->getProperty('strength'));
        $this->assertSame(16, $character->getProperty('dexterity'));
    }
}