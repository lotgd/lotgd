<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\GameState;
use LotGD2\Game\Enum\GameStateType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(GameState::class)]
class GameStateTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $gameState = new GameState();

        $this->assertNull($gameState->id);
        $this->assertNull($gameState->name);
        $this->assertSame([], $gameState->state);
        $this->assertSame(GameStateType::Setting, $gameState->type);
    }

    public function testConstructorWithArguments(): void
    {
        $gameState = new GameState(
            name: "server_motd",
            state: ["message" => "Welcome!"],
            type: GameStateType::Setting,
        );

        $this->assertNull($gameState->id);
        $this->assertSame("server_motd", $gameState->name);
        $this->assertSame(["message" => "Welcome!"], $gameState->state);
        $this->assertSame(GameStateType::Setting, $gameState->type);
    }

    public function testIdProperty(): void
    {
        $gameState = new GameState();
        $this->assertNull($gameState->id);

        $reflection = new ReflectionProperty(GameState::class, "id");
        $reflection->setValue($gameState, 42);

        $this->assertSame(42, $gameState->id);
    }

    public function testNameProperty(): void
    {
        $gameState = new GameState();

        $gameState->name = "daily_news";
        $this->assertSame("daily_news", $gameState->name);

        $gameState->name = null;
        $this->assertNull($gameState->name);
    }

    public function testTypeProperty(): void
    {
        $gameState = new GameState();

        $gameState->type = GameStateType::Setting;
        $this->assertSame(GameStateType::Setting, $gameState->type);
    }

    public function testStateWithArray(): void
    {
        $gameState = new GameState();

        $arrayData = ["key1" => "value1", "nested" => ["a" => 1]];
        $gameState->state = $arrayData;
        $this->assertSame($arrayData, $gameState->state);

        $listData = ["item1", "item2", "item3"];
        // @phpstan-ignore assign.propertyType
        $gameState->state = $listData;
        $this->assertSame($listData, $gameState->state);
    }

    #[TestWith(["hello world"])]
    #[TestWith([12345])]
    #[TestWith([99.95])]
    #[TestWith([true])]
    #[TestWith([false])]
    public function testStateWithScalarValues(mixed $scalarValue): void
    {
        $gameState = new GameState();
        $gameState->state = $scalarValue;

        $this->assertSame($scalarValue, $gameState->state);
    }

    public function testStateWithObject(): void
    {
        $gameState = new GameState();
        $object = (object) ["prop" => "test"];

        // @phpstan-ignore assign.propertyType
        $gameState->state = $object;
        $this->assertSame($object, $gameState->state);
    }

    public function testStateWhenUnderlyingPropertyIsNull(): void
    {
        $gameState = new GameState();

        $reflection = new ReflectionProperty(GameState::class, "state");
        $reflection->setValue($gameState, null);

        $this->assertNull($gameState->state);
    }

    public function testStateWithUnderlyingArrayContainingMagicKey(): void
    {
        $gameState = new GameState();
        $gameState->state = ["__" => "custom_wrapped_value"];

        $this->assertSame("custom_wrapped_value", $gameState->state);
    }
}
