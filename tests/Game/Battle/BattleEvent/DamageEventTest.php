<?php

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(DamageEvent::class)]
#[UsesClass(BattleMessage::class)]
class DamageEventTest extends TestCase
{
    private FighterInterface $attacker;
    private FighterInterface $defender;
    private CurrentCharacterFighter $currentCharacter;

    protected function setUp(): void
    {
        // Mock objects for testing
        $this->attacker = $this->createMock(FighterInterface::class);
        $this->attacker->method(PropertyHook::get("name"))->willReturn("Attacker");

        $this->defender = $this->createMock(FighterInterface::class);
        $this->defender->method(PropertyHook::get("name"))->willReturn("Defender");

        $this->currentCharacter = $this->createMock(CurrentCharacterFighter::class);
        $this->currentCharacter->method(PropertyHook::get("name"))->willReturn("Player");
    }

    /**
     * Test valid DamageEvent construction with positive damage
     */
    public function testConstructorWithValidDamage(): void
    {
        $context = ['damage' => 10];
        $event = new DamageEvent($this->attacker, $this->defender, $context);

        $this->assertInstanceOf(DamageEvent::class, $event);
        $this->assertEquals(10, $event->getDamage());
    }

    /**
     * Test constructor with zero damage
     */
    public function testConstructorWithZeroDamage(): void
    {
        $context = ['damage' => 0];
        $event = new DamageEvent($this->attacker, $this->defender, $context);

        $this->assertEquals(0, $event->getDamage());
    }

    /**
     * Test constructor with negative damage (reposte)
     */
    public function testConstructorWithNegativeDamage(): void
    {
        $context = ['damage' => -5];
        $event = new DamageEvent($this->attacker, $this->defender, $context);

        $this->assertEquals(-5, $event->getDamage());
    }

    /**
     * Test constructor requires damage parameter
     */
    public function testConstructorMissingDamageThrowsException(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);

        new DamageEvent($this->attacker, $this->defender, []);
    }

    /**
     * Test constructor with invalid damage type throws exception
     */
    public function testConstructorInvalidDamageTypeThrowsException(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);

        new DamageEvent($this->attacker, $this->defender, ['damage' => 'invalid']);
    }

    /**
     * Test apply method with positive damage damages the defender
     */
    public function testApplyPositiveDamageHitsDefender(): void
    {
        $this->defender->expects($this->once())
            ->method('damage')
            ->with(10);

        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 10]);
        $event->apply();
    }

    /**
     * Test apply method with negative damage damages the attacker (reposte)
     */
    public function testApplyNegativeDamageHitsAttacker(): void
    {
        $this->attacker->expects($this->once())
            ->method('damage')
            ->with(5);

        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => -5]);
        $event->apply();
    }

    /**
     * Test apply method with zero damage doesn't damage anyone
     */
    public function testApplyZeroDamageNoDamage(): void
    {
        $this->attacker->expects($this->never())->method('damage');
        $this->defender->expects($this->never())->method('damage');

        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 0]);
        $event->apply();
    }

    /**
     * Test getDamage returns correct value
     */
    public function testGetDamageReturnsCorrectValue(): void
    {
        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 25]);

        $this->assertEquals(25, $event->getDamage());
    }

    /**
     * Test decorate with zero damage and attacker is current character
     */
    public function testDecorateZeroDamageCurrentCharacterAttacker(): void
    {
        $event = new DamageEvent($this->currentCharacter, $this->defender, ['damage' => 0]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('You try to hit', $message->message);
        $this->assertStringContainsString('MISS', $message->message);
        $this->assertEquals('Defender', $message->context['defender']);
    }

    /**
     * Test decorate with zero damage and defender is current character
     */
    public function testDecorateZeroDamageCurrentCharacterDefender(): void
    {
        $event = new DamageEvent($this->attacker, $this->currentCharacter, ['damage' => 0]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('tries to hit you', $message->message);
        $this->assertStringContainsString('MISS', $message->message);
        $this->assertEquals('Attacker', $message->context['attacker']);
    }

    /**
     * Test decorate with zero damage and neither is current character
     */
    public function testDecorateZeroDamageNoCurrentCharacter(): void
    {
        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 0]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('tries to hit', $message->message);
        $this->assertStringContainsString('MISS', $message->message);
    }

    /**
     * Test decorate with positive damage and attacker is current character
     */
    public function testDecoratePositiveDamageCurrentCharacterAttacker(): void
    {
        $event = new DamageEvent($this->currentCharacter, $this->defender, ['damage' => 15]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('You hit', $message->message);
        $this->assertEquals('Defender', $message->context['defender']);
        $this->assertEquals(15, $message->context['damage']);
    }

    /**
     * Test decorate with positive damage and defender is current character
     */
    public function testDecoratePositiveDamageCurrentCharacterDefender(): void
    {
        $event = new DamageEvent($this->attacker, $this->currentCharacter, ['damage' => 20]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('hits you', $message->message);
        $this->assertEquals('Attacker', $message->context['attacker']);
        $this->assertEquals(20, $message->context['damage']);
    }

    /**
     * Test decorate with positive damage and neither is current character
     */
    public function testDecoratePositiveDamageNoCurrentCharacter(): void
    {
        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 12]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('hits', $message->message);
    }

    /**
     * Test decorate with negative damage (reposte) and attacker is current character
     */
    public function testDecorateNegativeDamageCurrentCharacterAttacker(): void
    {
        $event = new DamageEvent($this->currentCharacter, $this->defender, ['damage' => -8]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('RIPOSTE', $message->message);
        $this->assertEquals(8, $message->context['damage']);
    }

    /**
     * Test decorate with negative damage (reposte) and defender is current character
     */
    public function testDecorateNegativeDamageCurrentCharacterDefender(): void
    {
        $event = new DamageEvent($this->attacker, $this->currentCharacter, ['damage' => -6]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('RIPOSTE', $message->message);
        $this->assertEquals(6, $message->context['damage']);
    }

    /**
     * Test decorate with negative damage (reposte) and neither is current character
     */
    public function testDecorateNegativeDamageNoCurrentCharacter(): void
    {
        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => -3]);
        $event->apply();
        $message = $event->decorate();

        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertStringContainsString('they RIPOSTE', $message->message);
        $this->assertEquals(3, $message->context['damage']);
    }

    /**
     * Test decorate returns BattleMessage with correct context keys
     */
    public function testDecorateReturnsCorrectContext(): void
    {
        $event = new DamageEvent($this->attacker, $this->defender, ['damage' => 5]);
        $event->apply();
        $message = $event->decorate();

        $this->assertArrayHasKey('defender', $message->context);
        $this->assertArrayHasKey('attacker', $message->context);
        $this->assertArrayHasKey('damage', $message->context);
    }
}