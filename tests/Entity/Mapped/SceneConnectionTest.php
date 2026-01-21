<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Game\Enum\SceneConnectionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SceneConnection::class)]
#[UsesClass(Scene::class)]
class SceneConnectionTest extends TestCase
{
    public function testSceneConnectionConstructor()
    {
        $sceneConnection = new SceneConnection();

        $this->assertInstanceOf(SceneConnection::class, $sceneConnection);
    }

    public function testSceneConnectionConstructorArgumentNames()
    {
        $sourceScene = new Scene();
        $targetScene = new Scene();

        $sceneConnection = new SceneConnection(
            sourceScene: $sourceScene,
            targetScene: $targetScene,
            sourceLabel: "Hello World!",
            targetLabel: "Hello Moon!",
            type: SceneConnectionType::BothWays,
        );

        $this->assertSame($sourceScene, $sceneConnection->sourceScene);
        $this->assertSame($targetScene, $sceneConnection->targetScene);
        $this->assertSame("Hello World!", $sceneConnection->sourceLabel);
        $this->assertSame("Hello Moon!", $sceneConnection->targetLabel);
        $this->assertSame(SceneConnectionType::BothWays, $sceneConnection->type);
    }
}
