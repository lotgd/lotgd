<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Scene\SceneAttachment\SceneAttachmentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Stage::class)]
class StageTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $stage = new Stage();

        $this->assertInstanceOf(Stage::class, $stage);
        $this->assertNull($stage->id);
        $this->assertNull($stage->owner);
        $this->assertNull($stage->title);
        $this->assertNull($stage->description);
        $this->assertNull($stage->scene);
        $this->assertEquals([], $stage->actionGroups);
        $this->assertNull($stage->attachments);
        $this->assertNull($stage->context);
    }

    public function testConstructorWithAllParameters()
    {
        $owner = $this->createMock(Character::class);
        $scene = $this->createMock(Scene::class);
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');
        $actionGroups = ['test-group' => $actionGroup];
        $attachments = [];
        $context = ['key' => 'value'];

        $stage = new Stage(
            owner: $owner,
            title: "Forest Entrance",
            description: "You stand at the edge of a dark forest.",
            scene: $scene,
            actionGroups: $actionGroups,
            attachments: $attachments,
            context: $context
        );

        $this->assertSame($owner, $stage->owner);
        $this->assertSame("Forest Entrance", $stage->title);
        $this->assertSame("You stand at the edge of a dark forest.", $stage->description);
        $this->assertSame($scene, $stage->scene);
        $this->assertEquals($actionGroups, $stage->actionGroups);
        $this->assertEquals($attachments, $stage->attachments);
        $this->assertEquals($context, $stage->context);
    }

    public function testIdProperty()
    {
        $stage = new Stage();
        $this->assertNull($stage->id);

        // Note: ID would be set by Doctrine ORM in real usage
    }

    public function testOwnerProperty()
    {
        $stage = new Stage();
        $owner = $this->createMock(Character::class);

        // Test setter
        $stage->owner = $owner;
        $this->assertSame($owner, $stage->owner);

        // Test constructor assignment
        $owner2 = $this->createMock(Character::class);
        $stage2 = new Stage(owner: $owner2);
        $this->assertSame($owner2, $stage2->owner);

        // Test null assignment
        $stage->owner = null;
        $this->assertNull($stage->owner);
    }

    public function testTitleProperty()
    {
        $stage = new Stage();

        // Test setter
        $stage->title = "Village Square";
        $this->assertSame("Village Square", $stage->title);

        // Test constructor assignment
        $stage2 = new Stage(title: "Castle Throne Room");
        $this->assertSame("Castle Throne Room", $stage2->title);

        // Test null assignment
        $stage->title = null;
        $this->assertNull($stage->title);

        // Test empty string
        $stage->title = "";
        $this->assertSame("", $stage->title);

        // Test various titles
        $titles = [
            "Forest Path",
            "Ancient Dungeon",
            "Marketplace",
            "Wizard's Tower",
            "Dragon's Lair"
        ];

        foreach ($titles as $title) {
            $stage->title = $title;
            $this->assertSame($title, $stage->title);
        }
    }

    public function testDescriptionProperty()
    {
        $stage = new Stage();

        // Test setter
        $description = "A peaceful meadow with flowers blooming everywhere.";
        $stage->description = $description;
        $this->assertSame($description, $stage->description);

        // Test constructor assignment
        $constructorDescription = "Dark corridors echo with mysterious sounds.";
        $stage2 = new Stage(description: $constructorDescription);
        $this->assertSame($constructorDescription, $stage2->description);

        // Test null assignment
        $stage->description = null;
        $this->assertNull($stage->description);

        // Test empty string
        $stage->description = "";
        $this->assertSame("", $stage->description);

        // Test long description
        $longDescription = "This is a very long description that spans multiple lines and contains detailed information about the current location, including its atmosphere, surroundings, and potential interactions available to the player.";
        $stage->description = $longDescription;
        $this->assertSame($longDescription, $stage->description);
    }

    public function testAddDescription()
    {
        $stage = new Stage(description: "Initial description.");

        $result = $stage->addDescription("Additional text.");

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertSame("Initial description.\n\nAdditional text.", $stage->description);
    }

    public function testAddDescriptionWithCustomPrefix()
    {
        $stage = new Stage(description: "Start");

        $stage->addDescription("Middle", " -> ");
        $stage->addDescription("End", " | ");

        $this->assertSame("Start -> Middle | End", $stage->description);
    }

    public function testAddDescriptionToNullDescription()
    {
        $stage = new Stage();

        $stage->addDescription("New description");

        $this->assertSame("\n\nNew description", $stage->description);
    }

    public function testSceneProperty()
    {
        $stage = new Stage();
        $scene = $this->createMock(Scene::class);

        // Test setter
        $stage->scene = $scene;
        $this->assertSame($scene, $stage->scene);

        // Test constructor assignment
        $scene2 = $this->createMock(Scene::class);
        $stage2 = new Stage(scene: $scene2);
        $this->assertSame($scene2, $stage2->scene);

        // Test null assignment
        $stage->scene = null;
        $this->assertNull($stage->scene);
    }

    public function testActionGroupsProperty()
    {
        $stage = new Stage();

        // Test default empty array
        $this->assertEquals([], $stage->actionGroups);

        // Test setter
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');
        $actionGroups = ['test-group' => $actionGroup];

        $stage->actionGroups = $actionGroups;
        $this->assertEquals($actionGroups, $stage->actionGroups);

        // Test constructor assignment
        $stage2 = new Stage(actionGroups: $actionGroups);
        $this->assertEquals($actionGroups, $stage2->actionGroups);
    }

    public function testAddActionGroup()
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('combat-actions');

        $result = $stage->addActionGroup($actionGroup);

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertArrayHasKey('combat-actions', $stage->actionGroups);
        $this->assertSame($actionGroup, $stage->actionGroups['combat-actions']);
    }

    public function testAddMultipleActionGroups()
    {
        $stage = new Stage();

        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup1->method('getId')->willReturn('primary');

        $actionGroup2 = $this->createMock(ActionGroup::class);
        $actionGroup2->method('getId')->willReturn('secondary');

        $actionGroup3 = $this->createMock(ActionGroup::class);
        $actionGroup3->method('getId')->willReturn('special');

        $stage->addActionGroup($actionGroup1)
            ->addActionGroup($actionGroup2)
            ->addActionGroup($actionGroup3);

        $this->assertCount(3, $stage->actionGroups);
        $this->assertArrayHasKey('primary', $stage->actionGroups);
        $this->assertArrayHasKey('secondary', $stage->actionGroups);
        $this->assertArrayHasKey('special', $stage->actionGroups);
    }

    public function testAddActionGroup_ReplacesExisting()
    {
        $stage = new Stage();

        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup1->method('getId')->willReturn('test-group');

        $actionGroup2 = $this->createMock(ActionGroup::class);
        $actionGroup2->method('getId')->willReturn('test-group');

        $stage->addActionGroup($actionGroup1);
        $stage->addActionGroup($actionGroup2);

        $this->assertCount(1, $stage->actionGroups);
        $this->assertSame($actionGroup2, $stage->actionGroups['test-group']);
    }

    public function testAddAction()
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $action = $this->createMock(Action::class);

        $actionGroup->method('getId')->willReturn('test-group');
        $actionGroup->expects($this->once())
            ->method('addAction')
            ->with($action);

        $stage->addActionGroup($actionGroup);

        $result = $stage->addAction('test-group', $action);

        $this->assertSame($stage, $result); // Test fluent interface
    }

    public function testAddActionWithActionGroupObject()
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $action = $this->createMock(Action::class);

        $actionGroup->method('getId')->willReturn('test-group');
        $actionGroup->expects($this->once())
            ->method('addAction')
            ->with($action);

        $stage->addActionGroup($actionGroup);

        $result = $stage->addAction($actionGroup, $action);

        $this->assertSame($stage, $result);
    }

    public function testAddActionToNonExistentGroup()
    {
        $stage = new Stage();
        $action = $this->createMock(Action::class);

        // Should not throw an error, just do nothing
        $result = $stage->addAction('non-existent', $action);

        $this->assertSame($stage, $result);
    }

    public function testClearActionGroups()
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');

        $stage->addActionGroup($actionGroup);
        $this->assertCount(1, $stage->actionGroups);

        $result = $stage->clearActionGroups();

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertEquals([], $stage->actionGroups);
    }

    public function testAttachmentsProperty()
    {
        $stage = new Stage();

        // Test default null
        $this->assertNull($stage->attachments);

        // Test setter with empty array
        $stage->attachments = [];
        $this->assertEquals([], $stage->attachments);

        // Test constructor assignment
        $stage2 = new Stage(attachments: []);
        $this->assertEquals([], $stage2->attachments);

        // Test null assignment
        $stage->attachments = null;
        $this->assertNull($stage->attachments);
    }

    public function testAttachmentsSetterWithValidAttachments()
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);

        $attachments = [
            [
                "attachment" => $attachment,
                "config" => [],
                "data" => []
            ]
        ];

        $stage->attachments = $attachments;
        $this->assertEquals($attachments, $stage->attachments);
    }

    public function testAttachmentsSetterWithInvalidAttachments()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Attachment argument must be an instance of Attachment");

        $stage = new Stage();
        $invalidAttachments = [
            [
                "attachment" => "not an attachment object",
                "config" => [],
                "data" => []
            ]
        ];

        $stage->attachments = $invalidAttachments;
    }

    public function testAddAttachment()
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);
        $config = ['key' => 'value'];
        $data = ['data_key' => 'data_value'];

        $result = $stage->addAttachment($attachment, $config, $data);

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertCount(1, $stage->attachments);

        $addedAttachment = $stage->attachments[0];
        $this->assertSame($attachment, $addedAttachment['attachment']);
        $this->assertEquals($config, $addedAttachment['config']);
        $this->assertEquals($data, $addedAttachment['data']);
        $this->assertSame($stage, $result);
    }

    public function testAddAttachmentWithDefaults()
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);

        $stage->addAttachment($attachment);

        $addedAttachment = $stage->attachments[0];
        $this->assertSame($attachment, $addedAttachment['attachment']);
        $this->assertEquals([], $addedAttachment['config']);
        $this->assertEquals([], $addedAttachment['data']);
    }

    public function testAddMultipleAttachments()
    {
        $stage = new Stage();
        $attachment1 = $this->createMock(Attachment::class);
        $attachment2 = $this->createMock(Attachment::class);

        $stage->addAttachment($attachment1, ['config1' => 'value1'])
            ->addAttachment($attachment2, ['config2' => 'value2']);

        $this->assertCount(2, $stage->attachments);
        $this->assertSame($attachment1, $stage->attachments[0]['attachment']);
        $this->assertSame($attachment2, $stage->attachments[1]['attachment']);
    }

    public function testAddAttachmentToNullAttachments()
    {
        $stage = new Stage();
        $this->assertNull($stage->attachments);

        $attachment = $this->createMock(Attachment::class);
        $stage->addAttachment($attachment);

        $this->assertCount(1, $stage->attachments);
    }

    public function testClearAttachments()
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);

        $stage->addAttachment($attachment);
        $this->assertCount(1, $stage->attachments);

        $result = $stage->clearAttachments();

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertEquals([], $stage->attachments);
    }

    public function testContextProperty()
    {
        $stage = new Stage();

        // Test default null
        $this->assertNull($stage->context);

        // Test setter
        $context = ['key' => 'value', 'number' => 42];
        $stage->context = $context;
        $this->assertEquals($context, $stage->context);

        // Test constructor assignment
        $stage2 = new Stage(context: ['test' => 'data']);
        $this->assertEquals(['test' => 'data'], $stage2->context);

        // Test null assignment
        $stage->context = null;
        $this->assertNull($stage->context);
    }

    public function testAddContext()
    {
        $stage = new Stage();

        $result = $stage->addContext('player_level', 10);

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertEquals(['player_level' => 10], $stage->context);
    }

    public function testAddContextToNullContext()
    {
        $stage = new Stage();
        $this->assertNull($stage->context);

        $stage->addContext('key', 'value');

        $this->assertEquals(['key' => 'value'], $stage->context);
    }

    public function testAddMultipleContextEntries()
    {
        $stage = new Stage();

        $stage->addContext('health', 100)
            ->addContext('mana', 50)
            ->addContext('location', 'forest');

        $expected = [
            'health' => 100,
            'mana' => 50,
            'location' => 'forest'
        ];

        $this->assertEquals($expected, $stage->context);
    }

    public function testAddContextOverwritesExisting()
    {
        $stage = new Stage(context: ['key' => 'original']);

        $stage->addContext('key', 'updated');

        $this->assertEquals(['key' => 'updated'], $stage->context);
    }

    public function testClearContext()
    {
        $stage = new Stage(context: ['key' => 'value']);

        $result = $stage->clearContext();

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertEquals([], $stage->context);
    }

    public function testPreUpdateHook()
    {
        $actionGroup1 = $this->createMock(ActionGroup::class);
        $actionGroup2 = $this->createMock(ActionGroup::class);

        $actionGroup1->method('getId')->willReturn('group1');
        $actionGroup2->method('getId')->willReturn('group2');

        $stage = new Stage();
        $stage->addActionGroup($actionGroup1);
        $stage->addActionGroup($actionGroup2);

        $originalActionGroups = $stage->actionGroups;

        // Call preUpdate
        $stage->preUpdate();

        // Action groups should be cloned
        $this->assertCount(2, $stage->actionGroups);
        $this->assertArrayHasKey('group1', $stage->actionGroups);
        $this->assertArrayHasKey('group2', $stage->actionGroups);

        // But they should be different objects (cloned)
        $this->assertNotSame($originalActionGroups['group1'], $stage->actionGroups['group1']);
        $this->assertNotSame($originalActionGroups['group2'], $stage->actionGroups['group2']);
    }

    public function testCompleteStageScenario()
    {
        // Create a complete stage scenario
        $owner = $this->createMock(Character::class);
        $scene = $this->createMock(Scene::class);

        $stage = new Stage(
            owner: $owner,
            title: "Enchanted Forest",
            description: "Ancient trees tower above you.",
            scene: $scene,
            context: ['time_of_day' => 'noon']
        );

        // Add action groups
        $combatGroup = $this->createMock(ActionGroup::class);
        $combatGroup->method('getId')->willReturn('combat');

        $exploreGroup = $this->createMock(ActionGroup::class);
        $exploreGroup->method('getId')->willReturn('explore');

        $stage->addActionGroup($combatGroup)
            ->addActionGroup($exploreGroup);

        // Add description
        $stage->addDescription("A gentle breeze rustles the leaves.");

        // Add context
        $stage->addContext('weather', 'sunny')
            ->addContext('enemies_nearby', false);

        // Add attachment
        $attachment = $this->createMock(Attachment::class);
        $stage->addAttachment($attachment, ['priority' => 'high']);

        // Verify the complete state
        $this->assertSame($owner, $stage->owner);
        $this->assertSame("Enchanted Forest", $stage->title);
        $this->assertSame("Ancient trees tower above you.\n\nA gentle breeze rustles the leaves.", $stage->description);
        $this->assertSame($scene, $stage->scene);
        $this->assertCount(2, $stage->actionGroups);
        $this->assertCount(1, $stage->attachments);

        $expectedContext = [
            'time_of_day' => 'noon',
            'weather' => 'sunny',
            'enemies_nearby' => false
        ];
        $this->assertEquals($expectedContext, $stage->context);
    }

    public function testFluentInterfaceChaining()
    {
        $stage = new Stage();
        $owner = $this->createMock(Character::class);
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test');
        $attachment = $this->createStub(Attachment::class);

        // Test method chaining
        $result = $stage->addDescription("Initial description")
            ->addActionGroup($actionGroup)
            ->addAttachment($attachment)
            ->addContext('key', 'value')
            ->clearActionGroups()
            ->clearAttachments()
            ->clearContext();

        $this->assertSame($stage, $result);
        $this->assertEquals([], $stage->actionGroups);
        $this->assertEquals([], $stage->attachments);
        $this->assertEquals([], $stage->context);
    }
}