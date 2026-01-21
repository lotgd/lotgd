<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\BattleMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BattleMessage::class)]
class BattleMessageTest extends TestCase
{
    public function testConstructorWithSimpleMessage(): void
    {
        $message = "Player attacked the enemy";
        $context = [];

        $battleMessage = new BattleMessage($message, $context);

        $this->assertSame($message, $battleMessage->message);
        $this->assertSame($context, $battleMessage->context);
    }

    public function testConstructorWithContextData(): void
    {
        $message = "Player {player} attacked {enemy}";
        $context = [
            'player' => 'Hero',
            'enemy' => 'Goblin',
            'damage' => 15,
        ];

        $battleMessage = new BattleMessage($message, $context);

        $this->assertSame($message, $battleMessage->message);
        $this->assertSame($context, $battleMessage->context);
    }

    public function testConstructorWithEmptyMessage(): void
    {
        $message = "";
        $context = [];

        $battleMessage = new BattleMessage($message, $context);

        $this->assertSame($message, $battleMessage->message);
        $this->assertSame($context, $battleMessage->context);
    }

    public function testConstructorWithComplexContext(): void
    {
        $message = "Battle log entry";
        $context = [
            'attacker' => 'Warrior',
            'defender' => 'Enemy',
            'damage' => 25,
            'critical' => true,
            'timestamp' => 1234567890,
            'effects' => ['fire', 'poison'],
            'metadata' => [
                'level' => 10,
                'experience' => 100,
            ],
        ];

        $battleMessage = new BattleMessage($message, $context);

        $this->assertSame($message, $battleMessage->message);
        $this->assertSame($context, $battleMessage->context);
        $this->assertCount(7, $battleMessage->context);
    }

    public function testMessagePropertyIsReadable(): void
    {
        $message = "Test message";
        $battleMessage = new BattleMessage($message, []);

        $this->assertSame($message, $battleMessage->message);
    }

    public function testContextPropertyIsReadable(): void
    {
        $context = ['key' => 'value'];
        $battleMessage = new BattleMessage("Message", $context);

        $this->assertSame($context, $battleMessage->context);
    }

    public function testContextCanContainVariousTypes(): void
    {
        $context = [
            'string' => 'value',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => new \stdClass(),
        ];

        $battleMessage = new BattleMessage("Message", $context);

        $this->assertSame($context, $battleMessage->context);
    }

    public function testMultipleInstancesAreIndependent(): void
    {
        $message1 = "First attack";
        $context1 = ['damage' => 10];

        $message2 = "Second attack";
        $context2 = ['damage' => 20];

        $battleMessage1 = new BattleMessage($message1, $context1);
        $battleMessage2 = new BattleMessage($message2, $context2);

        $this->assertSame($message1, $battleMessage1->message);
        $this->assertSame($context1, $battleMessage1->context);
        $this->assertSame($message2, $battleMessage2->message);
        $this->assertSame($context2, $battleMessage2->context);
    }

    public function testContextWithUnicodeCharacters(): void
    {
        $message = "Battle message with unicode";
        $context = [
            'emoji' => '⚔️',
            'chinese' => '战斗',
            'arabic' => 'معركة',
        ];

        $battleMessage = new BattleMessage($message, $context);

        $this->assertSame($context, $battleMessage->context);
    }
}