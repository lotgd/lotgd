<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\DamageReflectionEvent;
use LotGD2\Game\Error\BattleEventError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(DamageReflectionEvent::class)]
#[UsesClass(BattleMessage::class)]
class DamageReflectionEventTest extends TestCase
{
    public function testEventConstructor(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'reflection' => 1.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
            ],
        );

        $this->assertInstanceOf(DamageReflectionEvent::class, $event);

        $this->expectException(BattleEventError::class);
        $event->decorate();
    }

    public function testEventBattleMessageIsNullIfNoMessageIsGiven(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $defender = $this->createStub(FighterInterface::class);

        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'reflection' => 1.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
            ],
        );

        $event->apply();

        $message = $event->decorate();
        $this->assertNull($message);
    }

    public function testEventIfTargetIsDefenderAndDamageIsPositive(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the damage reflection buff is the defender. Reflected damage should be lifeTap * damage.
         */
        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'reflection' => 1.0,
                'damageTarget' => "defender",
                'reflectionTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageTarget);
        $this->assertSame($attacker, $event->reflectionTarget);
        $this->assertEqualsWithDelta(10, $event->reflectedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(10, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageTarget"]);
        $this->assertSame($attacker, $message->context["reflectionTarget"]);
    }

    public function testEventToHaveNoEffectIfTargetIsDefenderAndDamageIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'reflection' => 1.0,
                'damageTarget' => "defender",
                'reflectionTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageTarget);
        $this->assertSame($attacker, $event->reflectionTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageTarget"]);
        $this->assertSame($attacker, $message->context["reflectionTarget"]);
    }

    public function testEventToHaveNoEffectIfTargetIsDefenderAndReflectionIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'reflection' => 0.0,
                'damageTarget' => "defender",
                'reflectionTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageTarget);
        $this->assertSame($attacker, $event->reflectionTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageTarget"]);
        $this->assertSame($attacker, $message->context["reflectionTarget"]);
    }

    public function testEventIfTargetIsDefenderAndDamageIsNegative(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'reflection' => 1.0,
                'damageTarget' => "defender",
                'reflectionTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageTarget);
        $this->assertSame($attacker, $event->reflectionTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("Effect failed", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect failed", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageTarget"]);
        $this->assertSame($attacker, $message->context["reflectionTarget"]);
    }

    public function testEventIfTargetIsAttackerAndDamageIsPositive(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the damage reflection buff is the defender. Reflected damage should be lifeTap * damage.
         */
        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'reflection' => 1.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->reflectionTarget);
        $this->assertSame($attacker, $event->damageTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("Effect failed", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect failed", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["reflectionTarget"]);
        $this->assertSame($attacker, $message->context["damageTarget"]);
    }

    public function testEventIfTargetIsAttackerAndDamageIsNegative(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the damage reflection buff is the defender. Reflected damage should be lifeTap * damage.
         */
        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'reflection' => 1.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->reflectionTarget);
        $this->assertSame($attacker, $event->damageTarget);
        $this->assertEqualsWithDelta(10, $event->reflectedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(10, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["reflectionTarget"]);
        $this->assertSame($attacker, $message->context["damageTarget"]);
    }

    function testEventToHasNoEffectIfTargetAttackerAndDamageIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the damage reflection buff is the defender. Reflected damage should be lifeTap * damage.
         */
        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'reflection' => 1.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->reflectionTarget);
        $this->assertSame($attacker, $event->damageTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["reflectionTarget"]);
        $this->assertSame($attacker, $message->context["damageTarget"]);
    }

    function testEventToHasNoEffectIfTargetAttackerAndReflectionIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the damage reflection buff is the defender. Reflected damage should be lifeTap * damage.
         */
        $event = new DamageReflectionEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'reflection' => 0.0,
                'damageTarget' => "attacker",
                'reflectionTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->reflectionTarget);
        $this->assertSame($attacker, $event->damageTarget);
        $this->assertEqualsWithDelta(0, $event->reflectedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["reflectedDamage"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["reflectionTarget"]);
        $this->assertSame($attacker, $message->context["damageTarget"]);
    }
}
