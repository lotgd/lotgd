<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\LifeTapEvent;
use LotGD2\Game\Error\BattleEventError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(LifeTapEvent::class)]
#[UsesClass(BattleMessage::class)]
class LifeTapEventTest extends TestCase
{
    public function testLifeTapEventConstructor(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
            ],
        );

        $this->assertInstanceOf(LifeTapEvent::class, $event);

        $this->expectException(BattleEventError::class);
        $event->decorate();
    }

    public function testLifeTapEventBattleMessageIsNullIfNoMessageIsGiven(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $defender = $this->createStub(FighterInterface::class);

        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
            ],
        );

        $event->apply();

        $message = $event->decorate();
        $this->assertNull($message);
    }

    public function testLifeTapEventIfTargetIsDefenderAndDamageIsPositive(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");
        $attacker->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $attacker->method(PropertyHook::get("health"))->willReturn(10);

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the lifetap buff is the defender. Healed damage should be lifeTap * damage.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'lifeTap' => 1.0,
                'damageVictim' => "defender",
                'healTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageVictim);
        $this->assertSame($attacker, $event->healTarget);
        $this->assertEqualsWithDelta(10, $event->healedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(10, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageVictim"]);
        $this->assertSame($attacker, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHasReducedEffectIfTargetIsDefenderAndDamageIsPositiveAndIsAtCloseToFullHealth(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");
        $attacker->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $attacker->method(PropertyHook::get("health"))->willReturn(15);

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the lifetap buff is the defender. Healed damage should be lifeTap * damage.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'lifeTap' => 1.0,
                'damageVictim' => "defender",
                'healTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageVictim);
        $this->assertSame($attacker, $event->healTarget);
        $this->assertEqualsWithDelta(5, $event->healedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(5, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageVictim"]);
        $this->assertSame($attacker, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHaveNoEffectIfTargetIsDefenderAndDamageIsPositiveAndIsAtFullHealth(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");
        $attacker->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $attacker->method(PropertyHook::get("health"))->willReturn(20);

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the lifetap buff is the defender. Healed damage should be lifeTap * damage.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'lifeTap' => 1.0,
                'damageVictim' => "defender",
                'healTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageVictim);
        $this->assertSame($attacker, $event->healTarget);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageVictim"]);
        $this->assertSame($attacker, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHaveNoEffectIfTargetIsDefenderAndDamageIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");
        $attacker->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $attacker->method(PropertyHook::get("health"))->willReturn(10);

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target of the lifetap buff is the defender. Healed damage should be lifeTap * damage.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'lifeTap' => 1.0,
                'damageVictim' => "defender",
                'healTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageVictim);
        $this->assertSame($attacker, $event->healTarget);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageVictim"]);
        $this->assertSame($attacker, $message->context["healTarget"]);
    }

    public function testLifeTapEventIfTargetIsDefenderAndDamageIsNegative(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target is the defender, but damage is negative.
         * Negative is a riposte, and the attacker actually takes damage.
         * The attack must fail, and healAmount must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'lifeTap' => 1.0,
                'damageVictim' => "defender",
                'healTarget' => "attacker",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($defender, $event->damageVictim);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("Effect failed", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect failed", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($defender, $message->context["damageVictim"]);
        $this->assertSame($attacker, $message->context["healTarget"]);
    }

    public function testLifeTapEventIfTargetIsAttackerAndDamageIsPositive(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");

        /**
         * The Target (of the buff) is the attacker. Damage is positive.
         *
         * This means that the defender takes damage. Healed Damage must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 10,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($attacker, $event->damageVictim);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("Effect failed", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect failed", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($attacker, $message->context["damageVictim"]);
        $this->assertSame($defender, $message->context["healTarget"]);
    }

    public function testLifeTapEventIfTargetIsAttackerAndDamageIsNegative(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");
        $defender->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $defender->method(PropertyHook::get("health"))->willReturn(10);

        /**
         * The Target is the attacker, and damage is negative.
         * Negative is a riposte, and the attacker actually takes damage.
         * As the target is the attacker, the riposte gets taped and healAmount must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($attacker, $event->damageVictim);
        $this->assertSame($defender, $event->healTarget);
        $this->assertEqualsWithDelta(10, $event->healedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(10, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($attacker, $message->context["damageVictim"]);
        $this->assertSame($defender, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHasReducedEffectIfTargetAttackerAndDamageIsNegativeAndIsAtCloseToFullHealth(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");
        $defender->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $defender->method(PropertyHook::get("health"))->willReturn(15);

        /**
         * The Target is the attacker, and damage is negative.
         * Negative is a riposte, and the attacker actually takes damage.
         * As the target is the attacker, the riposte gets taped and healAmount must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($attacker, $event->damageVictim);
        $this->assertSame($defender, $event->healTarget);
        $this->assertEqualsWithDelta(5, $event->healedDamage,0.0001);
        $this->assertSame("Effect succeeds", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("Effect succeeds", $message->message);
        $this->assertSame(5, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($attacker, $message->context["damageVictim"]);
        $this->assertSame($defender, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHasNoEffectIfTargetAttackerAndDamageIsNegativeAndIsAtFullHealth(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");
        $defender->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $defender->method(PropertyHook::get("health"))->willReturn(20);

        /**
         * The Target is the attacker, and damage is negative.
         * Negative is a riposte, and the attacker actually takes damage.
         * As the target is the attacker, the riposte gets taped and healAmount must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => -10,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($attacker, $event->damageVictim);
        $this->assertSame($defender, $event->healTarget);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($attacker, $message->context["damageVictim"]);
        $this->assertSame($defender, $message->context["healTarget"]);
    }

    public function testLifeTapEventToHasNoEffectIfTargetAttackerAndDamageIsZero(): void
    {
        $attacker = $this->createStub(FighterInterface::class);
        $attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $defender = $this->createStub(FighterInterface::class);
        $defender->method(PropertyHook::get("name"))->willReturn("Defender");
        $defender->method(PropertyHook::get("maxHealth"))->willReturn(20);
        $defender->method(PropertyHook::get("health"))->willReturn(20);

        /**
         * The Target is the attacker, and damage is negative.
         * Negative is a riposte, and the attacker actually takes damage.
         * As the target is the attacker, the riposte gets taped and healAmount must be 0.
         */
        $event = new LifeTapEvent(
            attacker: $attacker,
            defender: $defender,
            context: [
                'damage' => 0,
                'lifeTap' => 1.0,
                'damageVictim' => "attacker",
                'healTarget' => "defender",
                'effectFails' => "Effect failed",
                'effectSucceeds' => 'Effect succeeds',
                'noEffect' => "No effect",
            ],
        );

        $event->apply();

        $this->assertSame($attacker, $event->damageVictim);
        $this->assertSame($defender, $event->healTarget);
        $this->assertEqualsWithDelta(0, $event->healedDamage,0.0001);
        $this->assertSame("No effect", $event->message);

        $message = $event->decorate();
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertSame("No effect", $message->message);
        $this->assertSame(0, $message->context["heal"]);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
        $this->assertSame($attacker, $message->context["damageVictim"]);
        $this->assertSame($defender, $message->context["healTarget"]);
    }
}
