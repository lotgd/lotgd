<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Game\Enum\SceneConnectionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ValueError;

#[CoversClass(SceneConnection::class)]
#[UsesClass(Scene::class)]
#[UsesClass(SceneActionGroup::class)]
class SceneConnectionTest extends TestCase
{
    public function testEmptyConstructor(): void
    {
        $connection = new SceneConnection();

        $this->assertNull($connection->id);
        $this->assertNull($connection->sourceScene);
        $this->assertNull($connection->targetScene);
        $this->assertNull($connection->sourceLabel);
        $this->assertNull($connection->targetLabel);
        $this->assertSame(SceneConnectionType::BothWays, $connection->type);
        $this->assertNull($connection->sourceExpression);
        $this->assertNull($connection->targetExpression);
        $this->assertNull($connection->sourceActionGroup);
        $this->assertNull($connection->targetActionGroup);
    }

    public function testConstructorWithAllArguments(): void
    {
        $sourceScene = new Scene();
        $targetScene = new Scene();

        $connection = new SceneConnection(
            sourceScene: $sourceScene,
            targetScene: $targetScene,
            sourceLabel: "Go to Town",
            targetLabel: "Go to Forest",
            type: SceneConnectionType::ForwardOnly,
            sourceExpression: "character.level > 5",
            targetExpression: "character.gold >= 10",
        );

        $this->assertSame($sourceScene, $connection->sourceScene);
        $this->assertSame($targetScene, $connection->targetScene);
        $this->assertSame("Go to Town", $connection->sourceLabel);
        $this->assertSame("Go to Forest", $connection->targetLabel);
        $this->assertSame(SceneConnectionType::ForwardOnly, $connection->type);
        $this->assertSame("character.level > 5", $connection->sourceExpression);
        $this->assertSame("character.gold >= 10", $connection->targetExpression);
    }

    public function testIdProperty(): void
    {
        $connection = new SceneConnection();
        $this->assertNull($connection->id);

        $reflection = new ReflectionProperty(SceneConnection::class, "id");
        $reflection->setValue($connection, 55);

        $this->assertSame(55, $connection->id);
    }

    public function testPropertyModifications(): void
    {
        $connection = new SceneConnection();
        $sourceScene = new Scene();
        $targetScene = new Scene();

        $connection->sourceScene = $sourceScene;
        $connection->targetScene = $targetScene;
        $connection->sourceLabel = "North";
        $connection->targetLabel = "South";
        $connection->type = SceneConnectionType::ReverseOnly;
        $connection->sourceExpression = "hero.hp > 0";
        $connection->targetExpression = "hero.mana > 10";

        $this->assertSame($sourceScene, $connection->sourceScene);
        $this->assertSame($targetScene, $connection->targetScene);
        $this->assertSame("North", $connection->sourceLabel);
        $this->assertSame("South", $connection->targetLabel);
        $this->assertSame(SceneConnectionType::ReverseOnly, $connection->type);
        $this->assertSame("hero.hp > 0", $connection->sourceExpression);
        $this->assertSame("hero.mana > 10", $connection->targetExpression);

        $connection->sourceExpression = null;
        $connection->targetExpression = null;
        $this->assertNull($connection->sourceExpression);
        $this->assertNull($connection->targetExpression);
    }

    public function testSourceActionGroupGetter(): void
    {
        $sourceScene = new Scene();
        $connection = new SceneConnection(sourceScene: $sourceScene);

        $this->assertNull($connection->sourceActionGroup);

        $matchingGroup = new SceneActionGroup(title: "Source Group", scene: $sourceScene);
        $matchingGroup->addConnection($connection);

        $reflection = new ReflectionProperty(SceneConnection::class, "sceneActionGroup");
        $reflection->setValue($connection, new ArrayCollection([$matchingGroup]));

        $this->assertSame($matchingGroup, $connection->sourceActionGroup);

        // Group with different scene should not match
        $otherScene = new Scene();
        $otherGroup = new SceneActionGroup(title: "Other Group", scene: $otherScene);
        $otherGroup->addConnection($connection);
        $reflection->setValue($connection, new ArrayCollection([$otherGroup]));

        $this->assertNull($connection->sourceActionGroup);

        // Group not containing connection should not match
        $nonContainingGroup = new SceneActionGroup(title: "Non-containing", scene: $sourceScene);
        $reflection->setValue($connection, new ArrayCollection([$nonContainingGroup]));

        $this->assertNull($connection->sourceActionGroup);
    }

    public function testTargetActionGroupGetter(): void
    {
        $targetScene = new Scene();
        $connection = new SceneConnection(targetScene: $targetScene);

        $this->assertNull($connection->targetActionGroup);

        $matchingGroup = new SceneActionGroup(title: "Target Group", scene: $targetScene);
        $matchingGroup->addConnection($connection);

        $reflection = new ReflectionProperty(SceneConnection::class, "sceneActionGroup");
        $reflection->setValue($connection, new ArrayCollection([$matchingGroup]));

        $this->assertSame($matchingGroup, $connection->targetActionGroup);

        // Group with different scene should not match
        $otherScene = new Scene();
        $otherGroup = new SceneActionGroup(title: "Other Group", scene: $otherScene);
        $otherGroup->addConnection($connection);
        $reflection->setValue($connection, new ArrayCollection([$otherGroup]));

        $this->assertNull($connection->targetActionGroup);

        // Group not containing connection should not match
        $nonContainingGroup = new SceneActionGroup(title: "Non-containing", scene: $targetScene);
        $reflection->setValue($connection, new ArrayCollection([$nonContainingGroup]));

        $this->assertNull($connection->targetActionGroup);
    }

    public function testSourceActionGroupSetter(): void
    {
        $sourceScene = new Scene();
        $connection = new SceneConnection(sourceScene: $sourceScene);

        $sourceGroup = new SceneActionGroup(title: "Actions", scene: $sourceScene);
        $reflection = new ReflectionProperty(SceneConnection::class, "sceneActionGroup");
        $reflection->setValue($connection, new ArrayCollection([$sourceGroup]));

        $connection->sourceActionGroup = $sourceGroup;
        $this->assertTrue($sourceGroup->connections->contains($connection));

        // Setting to null should remove connection
        $connection->sourceActionGroup = null;
        $this->assertFalse($sourceGroup->connections->contains($connection));
    }

    public function testSourceActionGroupSetterWithSceneMismatchThrowsValueError(): void
    {
        $sourceScene = new Scene();
        $differentScene = new Scene();
        $connection = new SceneConnection(sourceScene: $sourceScene);
        $invalidGroup = new SceneActionGroup(title: "Wrong Scene", scene: $differentScene);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("The SceneActionGroup must belong to the same scene as this connection's source scene.");

        $connection->sourceActionGroup = $invalidGroup;
    }

    public function testTargetActionGroupSetter(): void
    {
        $targetScene = new Scene();
        $connection = new SceneConnection(targetScene: $targetScene);

        $targetGroup = new SceneActionGroup(title: "Target Actions", scene: $targetScene);
        $reflection = new ReflectionProperty(SceneConnection::class, "sceneActionGroup");
        $reflection->setValue($connection, new ArrayCollection([$targetGroup]));

        $connection->targetActionGroup = $targetGroup;
        $this->assertTrue($targetGroup->connections->contains($connection));

        // Setting to null should remove connection
        $connection->targetActionGroup = null;
        $this->assertFalse($targetGroup->connections->contains($connection));
    }

    public function testTargetActionGroupSetterWithSceneMismatchThrowsValueError(): void
    {
        $targetScene = new Scene();
        $differentScene = new Scene();
        $connection = new SceneConnection(targetScene: $targetScene);
        $invalidGroup = new SceneActionGroup(title: "Wrong Scene", scene: $differentScene);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("The SceneActionGroup must belong to the same scene as this connection's target scene.");

        $connection->targetActionGroup = $invalidGroup;
    }
}
