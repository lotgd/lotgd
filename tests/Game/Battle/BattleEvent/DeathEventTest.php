<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(DeathEvent::class)]
#[UsesClass(BattleMessage::class)]
class DeathEventTest extends TestCase
{
    private FighterInterface $attacker;
    private FighterInterface $defender;
    private FighterInterface $victim;

    protected function setUp(): void
    {
        $this->attacker = $this->createMock(FighterInterface::class);
        $this->defender = $this->createMock(FighterInterface::class);
        $this->victim = $this->createMock(FighterInterface::class);
    }

    public function testConstructorWithValidVictim(): void
    {
        $context = ['victim' => $this->victim];
        
        $event = new DeathEvent($this->attacker, $this->defender, $context);
        
        $this->assertInstanceOf(DeathEvent::class, $event);
    }

    public function testConstructorWithMissingVictim(): void
    {
        $this->expectException(MissingOptionsException::class);
        
        new DeathEvent($this->attacker, $this->defender, []);
    }

    public function testConstructorWithInvalidVictimType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        
        $context = ['victim' => 'invalid'];
        new DeathEvent($this->attacker, $this->defender, $context);
    }

    #[DoesNotPerformAssertions]
    public function testApplyDoesNotThrowException(): void
    {
        $context = ['victim' => $this->victim];
        $event = new DeathEvent($this->attacker, $this->defender, $context);
        
        $event->apply();
    }

    public function testDecorateWhenVictimIsCurrentCharacterFighter(): void
    {
        $currentCharacter = $this->createMock(CurrentCharacterFighter::class);
        $context = ['victim' => $currentCharacter];
        
        $event = new DeathEvent($this->attacker, $this->defender, $context);
        $message = $event->decorate();
        
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertEquals("You died.", $message->message);
        $this->assertEquals([], $message->context);
    }

    public function testDecorateWhenVictimIsNotCurrentCharacterFighter(): void
    {
        $victim = $this->createMock(FighterInterface::class);
        $victim->expects($this->once())->method(PropertyHook::get("name"))->willReturn("Enemy");
        
        $context = ['victim' => $victim];
        $event = new DeathEvent($this->attacker, $this->defender, $context);
        $event->apply();
        $message = $event->decorate();
        
        $this->assertInstanceOf(BattleMessage::class, $message);
        $this->assertEquals("You defeated {{ victim }}.", $message->message);
        $this->assertArrayHasKey('victim', $message->context);
        $this->assertEquals("Enemy", $message->context['victim']);
    }

    public function testDecorateReturnsCorrectMessageTemplate(): void
    {
        $victim = $this->createMock(FighterInterface::class);
        $victim->expects($this->once())->method(PropertyHook::get("name"))->willReturn("Goblin");
        
        $context = ['victim' => $victim];
        $event = new DeathEvent($this->attacker, $this->defender, $context);
        $message = $event->decorate();

        $this->assertSame("Goblin", $message->context['victim']);
        $this->assertStringContainsString('{{ victim }}', $message->message);
    }

    public function testMultipleEventsWithDifferentVictims(): void
    {
        $victim1 = $this->createMock(FighterInterface::class);
        $victim1->expects($this->once())->method(PropertyHook::get("name"))->willReturn("Enemy1");
        
        $victim2 = $this->createMock(FighterInterface::class);
        $victim2->expects($this->once())->method(PropertyHook::get("name"))->willReturn("Enemy2");
        
        $event1 = new DeathEvent($this->attacker, $this->defender, ['victim' => $victim1]);
        $event2 = new DeathEvent($this->attacker, $this->defender, ['victim' => $victim2]);

        $event1->apply();
        $event2->apply();
        
        $message1 = $event1->decorate();
        $message2 = $event2->decorate();

        $this->assertSame("Enemy1", $message1->context['victim']);
        $this->assertSame("Enemy2", $message2->context['victim']);
        $this->assertNotEquals($message1->context['victim'], $message2->context['victim']);
    }

    public function testConstructorAcceptsEmptyContextArray(): void
    {
        $this->expectException(MissingOptionsException::class);
        
        // Empty context should fail validation since 'victim' is required
        new DeathEvent($this->attacker, $this->defender, []);
    }

    public function testVictimIsRequiredInContext(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        
        $context = ['other_key' => $this->victim];
        new DeathEvent($this->attacker, $this->defender, $context);
    }
}