<?php

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(CriticalHitEvent::class)]
#[UsesClass(BattleMessage::class)]
class CriticalHitEventTest extends TestCase
{
    private FighterInterface $attacker;
    private FighterInterface $defender;
    private FighterInterface $currentCharacterAttacker;

    protected function setUp(): void
    {
        $this->attacker = $this->createMock(FighterInterface::class);
        $this->attacker->method(PropertyHook::get("name"))->willReturn("Enemy");
        $this->attacker->method(PropertyHook::get("attack"))->willReturn(10);

        $this->defender = $this->createMock(FighterInterface::class);
        $this->defender->method(PropertyHook::get("name"))->willReturn("Player");
        $this->defender->method(PropertyHook::get("attack"))->willReturn(5);

        $this->currentCharacterAttacker = $this->createMock(CurrentCharacterFighter::class);
        $this->currentCharacterAttacker->method(PropertyHook::get("name"))->willReturn("Hero");
        $this->currentCharacterAttacker->method(PropertyHook::get("attack"))->willReturn(10);
    }

    public function testConstructorWithValidContext(): void
    {
        $context = ["criticalAttackValue" => 50];
        $event = new CriticalHitEvent($this->attacker, $this->defender, $context);

        $this->assertInstanceOf(CriticalHitEvent::class, $event);
    }

    public function testConstructorWithMissingCriticalAttackValueThrowsException(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);
        
        new CriticalHitEvent($this->attacker, $this->defender, []);
    }

    public function testConstructorWithInvalidCriticalAttackValueTypeThrowsException(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);
        
        new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => "50"]);
    }

    public function testDecorateWithMegaPowerMoveRegularFighter(): void
    {
        $criticalValue = 50; // > 10 * 4 = 40
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("MEGA power move", $result->message);
        $this->assertStringContainsString("{{ attacker }}", $result->message);
    }

    public function testDecorateWithMegaPowerMoveCurrentCharacter(): void
    {
        $criticalValue = 50; // > 10 * 4 = 40
        $event = new CriticalHitEvent($this->currentCharacterAttacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("You execute a MEGA power move", $result->message);
        $this->assertStringNotContainsString("{{ attacker }}", $result->message);
    }

    public function testDecorateWithDoublePowerMoveRegularFighter(): void
    {
        $criticalValue = 35; // > 10 * 3 = 30, but <= 10 * 4 = 40
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("DOUBLE power move", $result->message);
    }

    public function testDecorateWithDoublePowerMoveCurrentCharacter(): void
    {
        $criticalValue = 35; // > 10 * 3 = 30, but <= 10 * 4 = 40
        $event = new CriticalHitEvent($this->currentCharacterAttacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("You execute a DOUBLE power move", $result->message);
    }

    public function testDecorateWithPowerMoveRegularFighter(): void
    {
        $criticalValue = 25; // > 10 * 2 = 20, but <= 10 * 3 = 30
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("power move", $result->message);
    }

    public function testDecorateWithPowerMoveCurrentCharacter(): void
    {
        $criticalValue = 25; // > 10 * 2 = 20, but <= 10 * 3 = 30
        $event = new CriticalHitEvent($this->currentCharacterAttacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("You execute a power move", $result->message);
    }

    public function testDecorateWithMinorPowerMoveRegularFighter(): void
    {
        $criticalValue = 15; // > 10 * 1.25 = 12.5, but <= 10 * 2 = 20
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("minor power move", $result->message);
    }

    public function testDecorateWithMinorPowerMoveCurrentCharacter(): void
    {
        $criticalValue = 15; // > 10 * 1.25 = 12.5, but <= 10 * 2 = 20
        $event = new CriticalHitEvent($this->currentCharacterAttacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $this->assertStringContainsString("You execute a minor power move", $result->message);
    }

    public function testDecorateWithNoCriticalReturnsNull(): void
    {
        $criticalValue = 10; // <= 10 * 1.25 = 12.5
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertNull($result);
    }

    public function testDecorateContextContainsFighterNames(): void
    {
        $criticalValue = 15;
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $result);
        $context = $result->context;
        $this->assertArrayHasKey("attacker", $context);
        $this->assertArrayHasKey("defender", $context);
        $this->assertEquals("Enemy", $context["attacker"]);
        $this->assertEquals("Player", $context["defender"]);
    }

    public function testBoundaryConditionMegaPowerMoveExactlyAboveThreshold(): void
    {
        $criticalValue = 41; // Just above 10 * 4 = 40
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertStringContainsString("MEGA power move", $result->message);
    }

    public function testBoundaryConditionMegaPowerMoveExactlyAtThreshold(): void
    {
        $criticalValue = 40; // Exactly 10 * 4 = 40
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertStringContainsString("DOUBLE power move", $result->message);
    }

    public function testBoundaryConditionMinorPowerMoveExactlyAtThreshold(): void
    {
        $criticalValue = 13; // Just above 10 * 1.25 = 12.5
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertStringContainsString("minor power move", $result->message);
    }

    public function testBoundaryConditionMinorPowerMoveBelowThreshold(): void
    {
        $criticalValue = 12; // Just below 10 * 1.25 = 12.5
        $event = new CriticalHitEvent($this->attacker, $this->defender, ["criticalAttackValue" => $criticalValue]);

        $event->apply();
        $result = $event->decorate();

        $this->assertNull($result);
    }
}