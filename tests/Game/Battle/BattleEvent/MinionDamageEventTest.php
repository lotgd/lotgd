<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\MinionDamageEvent;
use LotGD2\Game\Error\BattleEventError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(MinionDamageEvent::class)]
#[UsesClass(BattleMessage::class)]
class MinionDamageEventTest extends TestCase
{
    public function testEventConstructor(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new MinionDamageEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'target' => "attacker",
                'effectSucceeds' => "Effect succeeds",
                'effectFails' => "Effect fails",
                'noEffect' => "No Effect",
            ],
        );

        $this->expectException(BattleEventError::class);
        $event->decorate();
    }

    public function testEventBattleMessageIsNullIfNoMessageIsGiven(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $defender = $this->createStub(FighterInterface::class);

        $event = new MinionDamageEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'target' => "attacker",
            ],
        );

        $event->apply();

        $message = $event->decorate();
        $this->assertNull($message);
    }

    #[TestWith([10, "Effect succeeds"])]
    #[TestWith([-10, "Effect fails"])]
    #[TestWith([0, "No Effect"])]
    public function testEventIfTargetIsDefenderAndDifferentDamage(int $damageExpected, string $messageExpected): void
    {
        $attacker = $this->createMock(FighterInterface::class);
        $attacker->expects($this->never())->method("damage");
        $defender = $this->createMock(FighterInterface::class);
        $defender->expects($this->once())->method("damage")->with($this->equalTo($damageExpected));

        $event = new MinionDamageEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => $damageExpected,
                'target' => "defender",
                'effectSucceeds' => "Effect succeeds",
                'effectFails' => "Effect fails",
                'noEffect' => "No Effect",
            ],
        );

        // Check status of event first
        $event->apply();

        $this->assertSame($defender, $event->target);
        $this->assertSame("defender", $event->getContext()["target"]);
        $this->assertSame($damageExpected, $event->getContext()["damage"]);
        $this->assertSame("Effect succeeds", $event->getContext()["effectSucceeds"]);
        $this->assertSame("Effect fails", $event->getContext()["effectFails"]);
        $this->assertSame("No Effect", $event->getContext()["noEffect"]);

        // Check whether generated battle message is a) correct and b) contains all required context.
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);

        // Damage must be same
        $this->assertSame($messageExpected, $message->message);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($damageExpected, $message->context["damage"]);
        $this->assertSame("defender", $message->context["target"]);
        $this->assertSame($defender, $message->context["buffTarget"]);
    }

    #[TestWith([10, "Effect succeeds"])]
    #[TestWith([-10, "Effect fails"])]
    #[TestWith([0, "No Effect"])]
    public function testEventIfTargetIsAttackerAndDifferentDamage(int $damageExpected, string $messageExpected): void
    {
        $attacker = $this->createMock(FighterInterface::class);
        $attacker->expects($this->once())->method("damage")->with($this->equalTo($damageExpected));
        $defender = $this->createMock(FighterInterface::class);
        $defender->expects($this->never())->method("damage");

        $event = new MinionDamageEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => $damageExpected,
                'target' => "attacker",
                'effectSucceeds' => "Effect succeeds",
                'effectFails' => "Effect fails",
                'noEffect' => "No Effect",
            ],
        );

        // Check status of event first
        $event->apply();

        $this->assertSame($attacker, $event->target);
        $this->assertSame("attacker", $event->getContext()["target"]);
        $this->assertSame($damageExpected, $event->getContext()["damage"]);
        $this->assertSame("Effect succeeds", $event->getContext()["effectSucceeds"]);
        $this->assertSame("Effect fails", $event->getContext()["effectFails"]);
        $this->assertSame("No Effect", $event->getContext()["noEffect"]);

        // Check whether generated battle message is a) correct and b) contains all required context.
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);

        // Damage must be same
        $this->assertSame($messageExpected, $message->message);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($damageExpected, $message->context["damage"]);
        $this->assertSame("attacker", $message->context["target"]);
        $this->assertSame($attacker, $message->context["buffTarget"]);
    }
}
