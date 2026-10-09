<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Game\Scene\SceneAttachment\SceneAttachmentInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueError;

#[CoversClass(Stage::class)]
#[UsesClass(Paragraph::class)]
#[AllowMockObjectsWithoutExpectations]
class StageTest extends TestCase
{
    public function testEmptyConstructor(): void
    {
        $stage = new Stage();

        $this->assertInstanceOf(Stage::class, $stage);
        $this->assertNull($stage->id);
        $this->assertNull($stage->owner);
        $this->assertNull($stage->title);
        $this->assertNull($stage->scene);
        $this->assertEquals([], $stage->actionGroups);
        $this->assertNull($stage->attachments);
        $this->assertEmpty($stage->paragraphs);
    }

    public function testConstructorWithAllParameters(): void
    {
        $owner = $this->createMock(Character::class);
        $scene = $this->createMock(Scene::class);
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');
        $actionGroups = ['test-group' => $actionGroup];
        $attachments = [];
        $paragraphs = [
            $this->createMock(Paragraph::class),
        ];

        $paragraphs[0]->method(PropertyHook::get("id"))->willReturn("id-1");

        $stage = new Stage(
            owner: $owner,
            title: "Forest Entrance",
            paragraphs: $paragraphs,
            scene: $scene,
            actionGroups: $actionGroups,
            attachments: $attachments,
        );

        $this->assertSame($owner, $stage->owner);
        $this->assertSame("Forest Entrance", $stage->title);
        $this->assertSame($scene, $stage->scene);
        $this->assertEquals($actionGroups, $stage->actionGroups);
        $this->assertEquals($attachments, $stage->attachments);
        $this->assertSame(["id-1" => $paragraphs[0]], $stage->paragraphs);
    }

    public function testIdProperty(): void
    {
        $stage = new Stage();
        $this->assertNull($stage->id);

        // Note: ID would be set by Doctrine ORM in real usage
    }

    public function testOwnerProperty(): void
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

    public function testTitleProperty(): void
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

    public function testSceneProperty(): void
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

    public function testActionGroupsProperty(): void
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

    public function testAddActionGroup(): void
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('combat-actions');

        $result = $stage->addActionGroup($actionGroup);

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertArrayHasKey('combat-actions', $stage->actionGroups);
        $this->assertSame($actionGroup, $stage->actionGroups['combat-actions']);
    }

    public function testAddMultipleActionGroups(): void
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

    public function testAddActionGroup_ReplacesExisting(): void
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

    public function testAddAction(): void
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');
        $action = $this->createMock(Action::class);

        $actionGroup->expects($this->once())
            ->method('addAction')
            ->with($action);

        $stage->addActionGroup($actionGroup);

        $result = $stage->addAction('test-group', $action);

        $this->assertSame($stage, $result); // Test fluent interface
    }

    public function testAddActionWithActionGroupObject(): void
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test-group');
        $action = $this->createMock(Action::class);

        $actionGroup->expects($this->once())
            ->method('addAction')
            ->with($action);

        $stage->addActionGroup($actionGroup);

        $result = $stage->addAction($actionGroup, $action);

        $this->assertSame($stage, $result);
    }

    public function testAddActionToNonExistentGroup(): void
    {
        $stage = new Stage();
        $action = $this->createMock(Action::class);

        // Should not throw an error, just do nothing
        $this->expectException(ValueError::class);
        $result = $stage->addAction('non-existent', $action);

        $this->assertSame($stage, $result);
    }

    public function testClearActionGroups(): void
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

    public function testAttachmentsProperty(): void
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

    public function testAttachmentsSetterWithValidAttachments(): void
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

    public function testAttachmentsSetterWithInvalidAttachments(): void
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

        // @phpstan-ignore assign.propertyType
        $stage->attachments = $invalidAttachments;
    }

    public function testAddAttachment(): void
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

    public function testAddAttachmentWithDefaults(): void
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);

        $stage->addAttachment($attachment);

        $addedAttachment = $stage->attachments[0];
        $this->assertSame($attachment, $addedAttachment['attachment']);
        $this->assertEquals([], $addedAttachment['config']);
        $this->assertEquals([], $addedAttachment['data']);
    }

    public function testAddMultipleAttachments(): void
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

    public function testAddAttachmentToNullAttachments(): void
    {
        $stage = new Stage();
        $this->assertNull($stage->attachments);

        $attachment = $this->createMock(Attachment::class);
        $stage->addAttachment($attachment);

        $this->assertNotNull($stage->attachments);
        $this->assertCount(1, $stage->attachments);
    }

    public function testClearAttachments(): void
    {
        $stage = new Stage();
        $attachment = $this->createMock(Attachment::class);

        $stage->addAttachment($attachment);
        $this->assertCount(1, $stage->attachments);

        $result = $stage->clearAttachments();

        $this->assertSame($stage, $result); // Test fluent interface
        $this->assertEquals([], $stage->attachments);
    }

    public function testPreUpdateHook(): void
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

    public function testCompleteStageScenario(): void
    {
        // Create a complete stage scenario
        $owner = $this->createMock(Character::class);
        $scene = $this->createMock(Scene::class);
        $paragraph = $this->createStub(Paragraph::class);
        $paragraph
            ->method(PropertyHook::get("id"))
            ->willReturn('id-1');

        $paragraph->method(PropertyHook::get("text"))->willReturn("Ancient trees tower above you.");
        $paragraph->method(PropertyHook::get("context"))->willReturn(['time_of_day' => 'noon']);


        $paragraph2 = $this->createStub(Paragraph::class);
        $paragraph2
            ->method(PropertyHook::get("id"))
            ->willReturn('id-2');

        $paragraph2->method(PropertyHook::get("text"))->willReturn("A gentle breeze rustles the leaves.");
        $paragraph2->method(PropertyHook::get("context"))->willReturn(['weather' => 'sunny', "enemies_nearby", false]);

        $stage = new Stage(
            owner: $owner,
            title: "Enchanted Forest",
            paragraphs: [$paragraph],
            scene: $scene,
        );

        // Add action groups
        $combatGroup = $this->createMock(ActionGroup::class);
        $combatGroup->method('getId')->willReturn('combat');

        $exploreGroup = $this->createMock(ActionGroup::class);
        $exploreGroup->method('getId')->willReturn('explore');

        $stage->addActionGroup($combatGroup)
            ->addActionGroup($exploreGroup);

        // Add Paragraph
        $stage->addParagraph($paragraph2);

        // Add attachment
        $attachment = $this->createMock(Attachment::class);
        $stage->addAttachment($attachment, ['priority' => 'high']);

        // Verify the complete state
        $this->assertSame($owner, $stage->owner);
        $this->assertSame("Enchanted Forest", $stage->title);
        $this->assertSame($scene, $stage->scene);
        $this->assertCount(2, $stage->actionGroups);
        $this->assertCount(1, $stage->attachments);

        $this->assertCount(2, $stage->paragraphs);
        $this->assertSame($paragraph, $stage->paragraphs["id-1"]);
        $this->assertSame($paragraph2, $stage->paragraphs["id-2"]);
    }

    public function testFluentInterfaceChaining(): void
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method('getId')->willReturn('test');
        $attachment = $this->createStub(Attachment::class);
        $paragraph = $this->createStub(Paragraph::class);

        // Test method chaining
        $result = $stage
            ->addActionGroup($actionGroup)
            ->addAttachment($attachment)
            ->addParagraph($paragraph)
            ->clearActionGroups()
            ->clearAttachments()
            ->clearParagraphs();

        $this->assertSame($stage, $result);
        $this->assertEquals([], $stage->actionGroups);
        $this->assertEquals([], $stage->attachments);
        $this->assertEquals([], $stage->paragraphs);
    }

    public function testParagraphsSetterWithNull(): void
    {
        $stage = new Stage();
        $stage->paragraphs = null;
        $this->assertSame([], $stage->paragraphs);
    }

    public function testPropertiesAndPropertyTrait(): void
    {
        $stage = new Stage(properties: ["theme" => "dark"]);
        $this->assertSame(["theme" => "dark"], $stage->properties);

        $stage->properties = ["volume" => 80];
        $this->assertSame(["volume" => 80], $stage->properties);

        $this->assertSame(80, $stage->getProperty("volume"));
        $this->assertSame("default_val", $stage->getProperty("non_existent", "default_val"));
        $this->assertNull($stage->getProperty("non_existent"));

        $result = $stage->setProperty("music", "epic.mp3");
        $this->assertSame($stage, $result);
        $this->assertSame("epic.mp3", $stage->getProperty("music"));
        $this->assertSame(["volume" => 80, "music" => "epic.mp3"], $stage->getProperties());

        $result2 = $stage->setProperties(["volume" => 90, "difficulty" => "hard"]);
        $this->assertSame($stage, $result2);
        $this->assertSame(90, $stage->getProperty("volume"));
        $this->assertSame("hard", $stage->getProperty("difficulty"));
    }

    public function testClearMethod(): void
    {
        $stage = new Stage();
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->method("getId")->willReturn("action_1");
        $attachment = $this->createMock(Attachment::class);
        $paragraph = new Paragraph(id: "p1", text: "Text here");

        $stage->addActionGroup($actionGroup)
            ->addAttachment($attachment)
            ->addParagraph($paragraph);

        $this->assertCount(1, $stage->actionGroups);
        $this->assertCount(1, $stage->attachments);
        $this->assertCount(1, $stage->paragraphs);

        $result = $stage->clear();
        $this->assertSame($stage, $result);
        $this->assertSame([], $stage->actionGroups);
        $this->assertSame([], $stage->attachments);
        $this->assertSame([], $stage->paragraphs);
    }

    public function testPreUpdateClonesParagraphs(): void
    {
        $stage = new Stage();
        $paragraph = new Paragraph(id: "p1", text: "Some scene text");
        $stage->addParagraph($paragraph);

        $originalParagraph = $stage->paragraphs["p1"];
        $stage->preUpdate();

        $this->assertCount(1, $stage->paragraphs);
        $this->assertNotSame($originalParagraph, $stage->paragraphs["p1"]);
        $this->assertSame($originalParagraph->id, $stage->paragraphs["p1"]->id);
        $this->assertSame($originalParagraph->text, $stage->paragraphs["p1"]->text);
    }
}