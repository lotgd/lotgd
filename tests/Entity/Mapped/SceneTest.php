<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use ArrayAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(Scene::class)]
#[UsesClass(SceneConnection::class)]
class SceneTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $scene = new Scene();

        $this->assertNull($scene->title);
        $this->assertNull($scene->description);
        $this->assertNull($scene->templateClass);
        $this->assertEquals([], $scene->templateConfig);
        $this->assertFalse($scene->defaultScene);
        $this->assertInstanceOf(ArrayCollection::class, $scene->actionGroups);
        $this->assertInstanceOf(ArrayCollection::class, $scene->sourcedConnections);
        $this->assertInstanceOf(ArrayCollection::class, $scene->targetingConnections);
        $this->assertCount(0, $scene->actionGroups);
        $this->assertCount(0, $scene->sourcedConnections);
        $this->assertCount(0, $scene->targetingConnections);
    }

    public function testRequiredAttributes()
    {
        $scene = new Scene(
            title: "Scene title",
            description: "Scene description",
        );

        $this->assertSame("Scene title", $scene->title);
        $this->assertSame("Scene description", $scene->description);
    }

    public function testConstructorWithAllParameters()
    {
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $sourcedConnection = $this->createMock(SceneConnection::class);
        $targetingConnection = $this->createMock(SceneConnection::class);

        $scene = new Scene(
            title: "Test Scene",
            description: "Test Description",
            templateClass: null,
            templateConfig: ['key' => 'value'],
            sourcedConnections: [$sourcedConnection],
            targetingConnections: [$targetingConnection],
            actionGroups: [$actionGroup],
            defaultScene: true
        );

        $this->assertSame("Test Scene", $scene->title);
        $this->assertSame("Test Description", $scene->description);
        $this->assertEquals(['key' => 'value'], $scene->templateConfig);
        $this->assertTrue($scene->defaultScene);
        $this->assertCount(1, $scene->actionGroups);
        $this->assertCount(1, $scene->sourcedConnections);
        $this->assertCount(1, $scene->targetingConnections);
    }

    public function testIdProperty()
    {
        $scene = new Scene();
        $this->assertNull($scene->id);
    }

    public function testTemplateClassCannotBeSetToAClassNotImplementingSceneTemplateInterface()
    {
        $class = $this->createStub(ArrayAccess::class);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("The template class of a scene must implement");

        $scene = new Scene(
            templateClass: $class::class,
        );
    }

    public function testTemplateClassAcceptsClassImplementingSceneTemplateInterface()
    {
        $class = new class implements SceneTemplateInterface {
            public function onSceneLeave(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneEnter(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneChange(Stage $stage, Action $action, Scene $scene): void{}
            public static function validateConfiguration(array $config): array{return $config;}
        };

        $scene = new Scene(
            templateClass: $class::class,
        );

        $this->assertSame($class::class, $scene->templateClass);
    }

    public function testSettingTemplateClassCallsSceneTemplateValidation()
    {
        $class = new class implements SceneTemplateInterface {
            public function onSceneLeave(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneEnter(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneChange(Stage $stage, Action $action, Scene $scene): void{}
            public static function validateConfiguration(array $config): array
            {
                throw new Exception("validateConfiguration has been called");
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("validateConfiguration has been called");

        $scene = new Scene(
            templateClass: $class::class,
        );
    }

    public function testTemplateClassSetterValidation()
    {
        $scene = new Scene();
        
        $validClass = new class implements SceneTemplateInterface {
            public function onSceneLeave(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneEnter(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneChange(Stage $stage, Action $action, Scene $scene): void{}
            public static function validateConfiguration(array $config): array{return $config;}
        };

        $scene->templateClass = $validClass::class;
        $this->assertSame($validClass::class, $scene->templateClass);
    }

    public function testTemplateClassSetterThrowsExceptionForInvalidClass()
    {
        $scene = new Scene();
        
        $this->expectException(ValueError::class);
        $scene->templateClass = ArrayAccess::class;
    }

    public function testTemplateConfigSetterWithValidTemplateClass()
    {
        $class = new class implements SceneTemplateInterface {
            public function onSceneLeave(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneEnter(Stage $stage, Action $action, Scene $leavingScene, Scene $enteringScene): bool {return true;}
            public function onSceneChange(Stage $stage, Action $action, Scene $scene): void{}
            public static function validateConfiguration(array $config): array
            {
                return array_merge($config, ['validated' => true]);
            }
        };

        $scene = new Scene(templateClass: $class::class);
        $scene->templateConfig = ['test' => 'value'];
        
        $this->assertEquals(['test' => 'value', 'validated' => true], $scene->templateConfig);
    }

    public function testTemplateConfigSetterWithoutTemplateClass()
    {
        $scene = new Scene();
        $config = ['test' => 'value'];
        $scene->templateConfig = $config;
        
        $this->assertEquals($config, $scene->templateConfig);
    }

    public function testSourcedConnection()
    {
        $scene = new Scene(
            sourcedConnections: new ArrayCollection([
                $this->createStub(SceneConnection::class),
                $this->createStub(SceneConnection::class),
            ]),
        );

        $this->assertSame(2, $scene->getConnections()->count());
    }

    public function testActionGroupsProperty()
    {
        $actionGroup1 = $this->createMock(SceneActionGroup::class);
        $actionGroup2 = $this->createMock(SceneActionGroup::class);
        
        $scene = new Scene(actionGroups: [$actionGroup1, $actionGroup2]);
        
        $this->assertCount(2, $scene->actionGroups);
        $this->assertTrue($scene->actionGroups->contains($actionGroup1));
        $this->assertTrue($scene->actionGroups->contains($actionGroup2));
    }

    public function testActionGroupsSetter()
    {
        $scene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        
        $scene->actionGroups = [$actionGroup];
        
        $this->assertCount(1, $scene->actionGroups);
        $this->assertTrue($scene->actionGroups->contains($actionGroup));
    }

    public function testSourcedConnectionsSetter()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $scene->sourcedConnections = [$connection];
        
        $this->assertCount(1, $scene->sourcedConnections);
        $this->assertTrue($scene->sourcedConnections->contains($connection));
    }

    public function testTargetingConnectionsSetter()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $scene->targetingConnections = [$connection];
        
        $this->assertCount(1, $scene->targetingConnections);
        $this->assertTrue($scene->targetingConnections->contains($connection));
    }

    public function testConnectTo()
    {
        $sourceScene = new Scene();
        $targetScene = new Scene();
        
        $connection = $sourceScene->connectTo(
            $targetScene,
            SceneConnectionType::BothWays,
            "Go to target",
            "Return to source"
        );
        
        $this->assertInstanceOf(SceneConnection::class, $connection);
        $this->assertSame($sourceScene, $connection->sourceScene);
        $this->assertSame($targetScene, $connection->targetScene);
        $this->assertSame("Go to target", $connection->sourceLabel);
        $this->assertSame("Return to source", $connection->targetLabel);
        $this->assertSame(SceneConnectionType::BothWays, $connection->type);
        $this->assertTrue($sourceScene->sourcedConnections->contains($connection));
        $this->assertTrue($targetScene->targetingConnections->contains($connection));
    }

    public function testConnectToWithDefaults()
    {
        $sourceScene = new Scene();
        $targetScene = new Scene();
        
        $connection = $sourceScene->connectTo($targetScene);
        
        $this->assertSame(SceneConnectionType::BothWays, $connection->type);
        $this->assertNull($connection->sourceLabel);
        $this->assertNull($connection->targetLabel);
    }

    public function testAddSourcedConnection()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $result = $scene->addSourcedConnection($connection);
        
        $this->assertSame($scene, $result);
        $this->assertTrue($scene->sourcedConnections->contains($connection));
    }

    public function testAddSourcedConnectionDoesNotAddDuplicates()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $scene->addSourcedConnection($connection);
        $scene->addSourcedConnection($connection);
        
        $this->assertCount(1, $scene->sourcedConnections);
    }

    public function testRemoveSourcedConnection()
    {
        $scene = new Scene();
        $connection = new SceneConnection();
        $scene->addSourcedConnection($connection);
        
        $result = $scene->removeSourcedConnection($connection);
        
        $this->assertSame($scene, $result);
        $this->assertFalse($scene->sourcedConnections->contains($connection));
        $this->assertNull($connection->sourceScene);
    }

    public function testRemoveSourcedConnectionNotInCollection()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $result = $scene->removeSourcedConnection($connection);
        
        $this->assertSame($scene, $result);
    }

    public function testAddTargetingConnection()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $result = $scene->addTargetingConnection($connection);
        
        $this->assertSame($scene, $result);
        $this->assertTrue($scene->targetingConnections->contains($connection));
    }

    public function testAddTargetingConnectionDoesNotAddDuplicates()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $scene->addTargetingConnection($connection);
        $scene->addTargetingConnection($connection);
        
        $this->assertCount(1, $scene->targetingConnections);
    }

    public function testRemoveTargetingConnection()
    {
        $scene = new Scene();
        $connection = new SceneConnection();
        $scene->addTargetingConnection($connection);
        
        $result = $scene->removeTargetingConnection($connection);
        
        $this->assertSame($scene, $result);
        $this->assertFalse($scene->targetingConnections->contains($connection));
        $this->assertNull($connection->targetScene);
    }

    public function testRemoveTargetingConnectionNotInCollection()
    {
        $scene = new Scene();
        $connection = $this->createMock(SceneConnection::class);
        
        $result = $scene->removeTargetingConnection($connection);
        
        $this->assertSame($scene, $result);
    }

    public function testGetConnectionsAllConnections()
    {
        $scene = new Scene();
        $sourcedConnection = $this->createMock(SceneConnection::class);
        $targetingConnection = $this->createMock(SceneConnection::class);
        
        $scene->addSourcedConnection($sourcedConnection);
        $scene->addTargetingConnection($targetingConnection);
        
        $connections = $scene->getConnections();
        
        $this->assertCount(2, $connections);
        $this->assertTrue($connections->contains($sourcedConnection));
        $this->assertTrue($connections->contains($targetingConnection));
    }

    public function testGetConnectionsVisibleOnly()
    {
        $scene = new Scene();
        
        // Create connections with different types
        $bothWaysConnection = new SceneConnection(type: SceneConnectionType::BothWays);
        $forwardOnlyConnection = new SceneConnection(type: SceneConnectionType::ForwardOnly);
        $reverseOnlyConnection = new SceneConnection(type: SceneConnectionType::ReverseOnly);
        
        $scene->addSourcedConnection($bothWaysConnection);
        $scene->addSourcedConnection($forwardOnlyConnection);
        $scene->addTargetingConnection($reverseOnlyConnection);
        
        $visibleConnections = $scene->getConnections(true);
        
        // Should include BothWays and ForwardOnly from sourced, and ReverseOnly from targeting
        $this->assertCount(3, $visibleConnections);
        $this->assertTrue($visibleConnections->contains($bothWaysConnection));
        $this->assertTrue($visibleConnections->contains($forwardOnlyConnection));
        $this->assertTrue($visibleConnections->contains($reverseOnlyConnection));
    }

    public function testGetConnectionsVisibleOnlyFiltersCorrectly()
    {
        $scene = new Scene();
        
        // Create sourced connection that should NOT be visible (ReverseOnly)
        $sourcedReverseConnection = new SceneConnection(type: SceneConnectionType::ReverseOnly);
        // Create targeting connection that should NOT be visible (ForwardOnly)
        $targetingForwardConnection = new SceneConnection(type: SceneConnectionType::ForwardOnly);
        
        $scene->addSourcedConnection($sourcedReverseConnection);
        $scene->addTargetingConnection($targetingForwardConnection);
        
        $visibleConnections = $scene->getConnections(true);
        
        $this->assertCount(0, $visibleConnections);
    }

    public function testAddActionGroup()
    {
        $scene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $actionGroup->expects($this->once())
                   ->method('setScene')
                   ->with($scene);
        
        $result = $scene->addActionGroup($actionGroup);
        
        $this->assertSame($scene, $result);
        $this->assertTrue($scene->actionGroups->contains($actionGroup));
    }

    public function testAddActionGroupDoesNotAddDuplicates()
    {
        $scene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $actionGroup->expects($this->once())
                   ->method('setScene')
                   ->with($scene);
        
        $scene->addActionGroup($actionGroup);
        $scene->addActionGroup($actionGroup);
        
        $this->assertCount(1, $scene->actionGroups);
    }

    public function testRemoveActionGroup()
    {
        $scene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $actionGroup->expects($this->once())
                   ->method('getScene')
                   ->willReturn($scene);
        $actionGroup->expects($this->exactly(2))
                   ->method('setScene')
                   ->willReturnMap([
                       [$scene, $actionGroup],
                       [null, $actionGroup]
                   ]);
        
        $scene->addActionGroup($actionGroup);
        $result = $scene->removeActionGroup($actionGroup);
        
        $this->assertSame($scene, $result);
        $this->assertFalse($scene->actionGroups->contains($actionGroup));
    }

    public function testRemoveActionGroupNotInCollection()
    {
        $scene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $actionGroup->expects($this->never())
                   ->method('setScene');
        
        $result = $scene->removeActionGroup($actionGroup);
        
        $this->assertSame($scene, $result);
    }

    public function testRemoveActionGroupWithDifferentScene()
    {
        $scene = new Scene();
        $otherScene = new Scene();
        $actionGroup = $this->createMock(SceneActionGroup::class);
        $actionGroup->expects($this->once())
                   ->method('getScene')
                   ->willReturn($otherScene);
        $actionGroup->expects($this->once())
                   ->method('setScene');
        
        $scene->addActionGroup($actionGroup);
        $scene->addActionGroup($actionGroup);
        $scene->removeActionGroup($actionGroup);
    }

    public function testIsDefaultScene()
    {
        $defaultScene = new Scene(defaultScene: true);
        $nonDefaultScene = new Scene(defaultScene: false);
        $nullDefaultScene = new Scene(defaultScene: null);
        
        $this->assertTrue($defaultScene->isDefaultScene());
        $this->assertFalse($nonDefaultScene->isDefaultScene());
        $this->assertFalse($nullDefaultScene->isDefaultScene());
    }

    public function testDefaultSceneDefaultValue()
    {
        $scene = new Scene();
        $this->assertFalse($scene->isDefaultScene());
        $this->assertFalse($scene->defaultScene);
    }
}