<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Entity\Mapped\SceneConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SceneActionGroup::class)]
class SceneActionGroupTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $actionGroup = new SceneActionGroup();

        $this->assertInstanceOf(SceneActionGroup::class, $actionGroup);
        $this->assertNull($actionGroup->id);
        $this->assertNull($actionGroup->title);
        $this->assertNull($actionGroup->scene);
        $this->assertSame(0, $actionGroup->sorting);
        $this->assertInstanceOf(ArrayCollection::class, $actionGroup->connections);
        $this->assertCount(0, $actionGroup->connections);
    }

    public function testConstructorWithAllParameters()
    {
        $scene = $this->createMock(Scene::class);
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connections = [$connection1, $connection2];

        $actionGroup = new SceneActionGroup(
            title: "Main Actions",
            connections: $connections,
            scene: $scene,
            sorting: 10
        );

        $this->assertSame("Main Actions", $actionGroup->title);
        $this->assertSame($scene, $actionGroup->scene);
        $this->assertSame(10, $actionGroup->sorting);
        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
    }

    public function testIdProperty()
    {
        $actionGroup = new SceneActionGroup();
        $this->assertNull($actionGroup->id);

        // Note: ID would be set by Doctrine ORM in real usage
    }

    public function testTitleProperty()
    {
        $actionGroup = new SceneActionGroup();

        // Test setter
        $actionGroup->title = "Combat Actions";
        $this->assertSame("Combat Actions", $actionGroup->title);

        // Test constructor assignment
        $actionGroup2 = new SceneActionGroup(title: "Exploration Actions");
        $this->assertSame("Exploration Actions", $actionGroup2->title);

        // Test null assignment
        $actionGroup->title = null;
        $this->assertNull($actionGroup->title);

        // Test empty string
        $actionGroup->title = "";
        $this->assertSame("", $actionGroup->title);

        // Test various action group titles
        $titles = [
            "Primary Actions",
            "Secondary Options",
            "Special Abilities",
            "Movement",
            "Inventory"
        ];

        foreach ($titles as $title) {
            $actionGroup->title = $title;
            $this->assertSame($title, $actionGroup->title);
        }
    }

    public function testSceneProperty()
    {
        $actionGroup = new SceneActionGroup();
        $scene = $this->createMock(Scene::class);

        // Test setter
        $actionGroup->scene = $scene;
        $this->assertSame($scene, $actionGroup->scene);

        // Test constructor assignment
        $scene2 = $this->createMock(Scene::class);
        $actionGroup2 = new SceneActionGroup(scene: $scene2);
        $this->assertSame($scene2, $actionGroup2->scene);

        // Test null assignment
        $actionGroup->scene = null;
        $this->assertNull($actionGroup->scene);
    }

    public function testSortingProperty()
    {
        $actionGroup = new SceneActionGroup();

        // Test default value
        $this->assertSame(0, $actionGroup->sorting);

        // Test setter
        $actionGroup->sorting = 5;
        $this->assertSame(5, $actionGroup->sorting);

        // Test constructor assignment
        $actionGroup2 = new SceneActionGroup(sorting: 15);
        $this->assertSame(15, $actionGroup2->sorting);

        // Test negative values
        $actionGroup->sorting = -10;
        $this->assertSame(-10, $actionGroup->sorting);

        // Test large values
        $actionGroup->sorting = 1000;
        $this->assertSame(1000, $actionGroup->sorting);
    }

    public function testConnectionsProperty()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(connections: [$connection1, $connection2]);

        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
    }

    public function testConnectionsSetter()
    {
        $actionGroup = new SceneActionGroup();
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        $actionGroup->connections = [$connection1, $connection2];

        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
    }

    public function testConnectionsSetterReplacesExistingConnections()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connection3 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(connections: [$connection1, $connection2]);

        // Replace with new connections
        $actionGroup->connections = [$connection3];

        $this->assertCount(1, $actionGroup->connections);
        $this->assertFalse($actionGroup->connections->contains($connection1));
        $this->assertFalse($actionGroup->connections->contains($connection2));
        $this->assertTrue($actionGroup->connections->contains($connection3));
    }

    public function testAddConnection()
    {
        $actionGroup = new SceneActionGroup();
        $connection = $this->createMock(SceneConnection::class);

        $result = $actionGroup->addConnection($connection);

        $this->assertSame($actionGroup, $result); // Test fluent interface
        $this->assertTrue($actionGroup->connections->contains($connection));
        $this->assertCount(1, $actionGroup->connections);
    }

    public function testAddConnectionDoesNotAddDuplicates()
    {
        $actionGroup = new SceneActionGroup();
        $connection = $this->createMock(SceneConnection::class);

        $actionGroup->addConnection($connection);
        $actionGroup->addConnection($connection); // Try to add the same connection again

        $this->assertCount(1, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection));
    }

    public function testAddMultipleConnections()
    {
        $actionGroup = new SceneActionGroup();
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connection3 = $this->createMock(SceneConnection::class);

        $actionGroup->addConnection($connection1)
            ->addConnection($connection2)
            ->addConnection($connection3);

        $this->assertCount(3, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
        $this->assertTrue($actionGroup->connections->contains($connection3));
    }

    public function testRemoveConnection()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(connections: [$connection1, $connection2]);

        $result = $actionGroup->removeConnection($connection1);

        $this->assertSame($actionGroup, $result); // Test fluent interface
        $this->assertFalse($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
        $this->assertCount(1, $actionGroup->connections);
    }

    public function testRemoveConnectionNotInCollection()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(connections: [$connection1]);

        $result = $actionGroup->removeConnection($connection2); // Try to remove non-existing connection

        $this->assertSame($actionGroup, $result);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertCount(1, $actionGroup->connections);
    }

    public function testRemoveAllConnections()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connection3 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(connections: [$connection1, $connection2, $connection3]);

        $actionGroup->removeConnection($connection1)
            ->removeConnection($connection2)
            ->removeConnection($connection3);

        $this->assertCount(0, $actionGroup->connections);
    }

    public function testCompleteScenario()
    {
        // Test creating a complete action group and modifying it
        $scene = $this->createMock(Scene::class);
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        $actionGroup = new SceneActionGroup(
            title: "Battle Options",
            connections: [$connection1],
            scene: $scene,
            sorting: 100
        );

        // Verify initial state
        $this->assertSame("Battle Options", $actionGroup->title);
        $this->assertSame($scene, $actionGroup->scene);
        $this->assertSame(100, $actionGroup->sorting);
        $this->assertCount(1, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));

        // Modify the action group
        $actionGroup->title = "Combat Actions";
        $actionGroup->sorting = 50;
        $actionGroup->addConnection($connection2);

        // Verify modifications
        $this->assertSame("Combat Actions", $actionGroup->title);
        $this->assertSame(50, $actionGroup->sorting);
        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));

        // Scene should remain unchanged
        $this->assertSame($scene, $actionGroup->scene);
    }

    public function testFluentInterfaceChaining()
    {
        $actionGroup = new SceneActionGroup();
        $scene = $this->createMock(Scene::class);
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);

        // Test method chaining
        $result = $actionGroup
            ->addConnection($connection1)
            ->addConnection($connection2);

        $this->assertSame($actionGroup, $result);
        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
    }

    public function testConnectionManagementCombinations()
    {
        $actionGroup = new SceneActionGroup();
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connection3 = $this->createMock(SceneConnection::class);

        // Test various combinations of adding and removing
        $actionGroup->addConnection($connection1);
        $actionGroup->addConnection($connection2);
        $this->assertCount(2, $actionGroup->connections);

        $actionGroup->removeConnection($connection1);
        $this->assertCount(1, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection2));

        $actionGroup->addConnection($connection3);
        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection2));
        $this->assertTrue($actionGroup->connections->contains($connection3));

        // Replace all connections using setter
        $actionGroup->connections = [$connection1];
        $this->assertCount(1, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertFalse($actionGroup->connections->contains($connection2));
        $this->assertFalse($actionGroup->connections->contains($connection3));
    }

    public function testConstructorWithEmptyConnections()
    {
        $actionGroup = new SceneActionGroup(
            title: "Empty Group",
            connections: [],
            sorting: 5
        );

        $this->assertSame("Empty Group", $actionGroup->title);
        $this->assertSame(5, $actionGroup->sorting);
        $this->assertCount(0, $actionGroup->connections);
    }

    public function testConstructorWithArrayCollection()
    {
        $connection1 = $this->createMock(SceneConnection::class);
        $connection2 = $this->createMock(SceneConnection::class);
        $connections = new ArrayCollection([$connection1, $connection2]);

        $actionGroup = new SceneActionGroup(connections: $connections);

        $this->assertCount(2, $actionGroup->connections);
        $this->assertTrue($actionGroup->connections->contains($connection1));
        $this->assertTrue($actionGroup->connections->contains($connection2));
    }

    public function testSortingWithDifferentValues()
    {
        // Test creating action groups with different sorting values
        $actionGroup1 = new SceneActionGroup(title: "First", sorting: 10);
        $actionGroup2 = new SceneActionGroup(title: "Second", sorting: 5);
        $actionGroup3 = new SceneActionGroup(title: "Third", sorting: 15);

        $this->assertSame(10, $actionGroup1->sorting);
        $this->assertSame(5, $actionGroup2->sorting);
        $this->assertSame(15, $actionGroup3->sorting);

        // Modify sorting
        $actionGroup1->sorting = 1;
        $actionGroup2->sorting = 2;
        $actionGroup3->sorting = 3;

        $this->assertSame(1, $actionGroup1->sorting);
        $this->assertSame(2, $actionGroup2->sorting);
        $this->assertSame(3, $actionGroup3->sorting);
    }
}