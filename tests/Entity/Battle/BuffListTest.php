<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\BuffMessageEvent;
use LotGD2\Game\Battle\BattleEvent\DamageReflectionEvent;
use LotGD2\Game\Battle\BattleEvent\LifeTapEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(BuffList::class)]
#[UsesClass(LifeTapEvent::class)]
#[UsesClass(DamageReflectionEvent::class)]
#[UsesClass(BattleMessage::class)]
#[UsesClass(BuffMessageEvent::class)]
class BuffListTest extends TestCase
{
    public function testProcessDamageDependentBuffsForBadGuyLifeTap()
    {
        $partialMock = $this->createPartialMock(BuffList::class, [
        ]);

        $buffMock = $this->createMock(Buff::class, []);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyLifeTap"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyLifeTap"))
            ->willReturn(1.0);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectSuccessMessage"))
            ->willReturn("Effect succeeded");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectFailsMessage"))
            ->willReturn("Effect failed");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("noEffectMessage"))
            ->willReturn("Effect had no effect");

        $partialMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("activeBuffs"))
            ->willReturn([
                Buff::ACTIVATES_ON_OFFENSE_TURN => [
                    $buffMock,
                ]
            ]);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('GoodGuy');
        $badGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('BadGuy');

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, 10, $goodGuy, $badGuy);
        $this->assertCount(0, $events);

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, 10, $goodGuy, $badGuy);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(LifeTapEvent::class, $events[0]);

        /** @var LifeTapEvent $event */
        $event = $events[0];

        $this->assertSame("defender", $event->context["damageVictim"]);
        $this->assertSame("attacker", $event->context["healTarget"]);
        $this->assertSame(10, $event->context["damage"]);
        $this->assertEqualsWithDelta(1., $event->context["lifeTap"], 0.001);
        $this->assertSame("Effect succeeded", $event->context["effectSucceeds"]);
        $this->assertSame("Effect failed", $event->context["effectFails"]);
        $this->assertSame("Effect had no effect", $event->context["noEffect"]);
    }

    public function testProcessDamageDependentBuffsForGoodGuyLifeTap()
    {
        $partialMock = $this->createPartialMock(BuffList::class, [
        ]);

        $buffMock = $this->createMock(Buff::class, []);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyLifeTap"))
            ->willReturn(0.5);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyLifeTap"))
            ->willReturn(0.0);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectSuccessMessage"))
            ->willReturn("Effect succeeded");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectFailsMessage"))
            ->willReturn("Effect failed");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("noEffectMessage"))
            ->willReturn("Effect had no effect");

        $partialMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("activeBuffs"))
            ->willReturn([
                Buff::ACTIVATES_ON_OFFENSE_TURN => [
                    $buffMock,
                ]
            ]);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('GoodGuy');
        $badGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('BadGuy');

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, 10, $goodGuy, $badGuy);
        $this->assertCount(0, $events);

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, 15, $goodGuy, $badGuy);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(LifeTapEvent::class, $events[0]);

        /** @var LifeTapEvent $event */
        $event = $events[0];

        $this->assertSame("attacker", $event->context["damageVictim"]);
        $this->assertSame("defender", $event->context["healTarget"]);
        $this->assertSame(15, $event->context["damage"]);
        $this->assertEqualsWithDelta(0.5, $event->context["lifeTap"], 0.001);
        $this->assertSame("Effect succeeded", $event->context["effectSucceeds"]);
        $this->assertSame("Effect failed", $event->context["effectFails"]);
        $this->assertSame("Effect had no effect", $event->context["noEffect"]);
    }

    public function testProcessDamageDependentBuffsForBadGuyDamageReflection()
    {
        $partialMock = $this->createPartialMock(BuffList::class, [
        ]);

        $buffMock = $this->createMock(Buff::class, []);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyLifeTap"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyLifeTap"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyDamageReflection"))
            ->willReturn(1.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectSuccessMessage"))
            ->willReturn("Effect succeeded");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectFailsMessage"))
            ->willReturn("Effect failed");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("noEffectMessage"))
            ->willReturn("Effect had no effect");

        $partialMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("activeBuffs"))
            ->willReturn([
                Buff::ACTIVATES_ON_OFFENSE_TURN => [
                    $buffMock,
                ]
            ]);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('GoodGuy');
        $badGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('BadGuy');

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, 10, $goodGuy, $badGuy);
        $this->assertCount(0, $events);

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, 10, $goodGuy, $badGuy);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DamageReflectionEvent::class, $events[0]);

        /** @var LifeTapEvent $event */
        $event = $events[0];

        $this->assertSame("defender", $event->context["damageTarget"]);
        $this->assertSame("attacker", $event->context["reflectionTarget"]);
        $this->assertSame(10, $event->context["damage"]);
        $this->assertEqualsWithDelta(1., $event->context["reflection"], 0.001);
        $this->assertSame("Effect succeeded", $event->context["effectSucceeds"]);
        $this->assertSame("Effect failed", $event->context["effectFails"]);
        $this->assertSame("Effect had no effect", $event->context["noEffect"]);
    }

    public function testProcessDamageDependentBuffsForGoodGuyDamageReflection()
    {
        $partialMock = $this->createPartialMock(BuffList::class, [
        ]);

        $buffMock = $this->createMock(Buff::class, []);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyLifeTap"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyLifeTap"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("goodGuyDamageReflection"))
            ->willReturn(1.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("badGuyDamageReflection"))
            ->willReturn(0.);
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectSuccessMessage"))
            ->willReturn("Effect succeeded");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("effectFailsMessage"))
            ->willReturn("Effect failed");
        $buffMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("noEffectMessage"))
            ->willReturn("Effect had no effect");

        $partialMock
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("activeBuffs"))
            ->willReturn([
                Buff::ACTIVATES_ON_OFFENSE_TURN => [
                    $buffMock,
                ]
            ]);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('GoodGuy');
        $badGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn('BadGuy');

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_DEFENSE_TURN, 10, $goodGuy, $badGuy);
        $this->assertCount(0, $events);

        $events = $partialMock->processDamageDependentBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, 10, $goodGuy, $badGuy);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DamageReflectionEvent::class, $events[0]);

        /** @var LifeTapEvent $event */
        $event = $events[0];

        $this->assertSame("attacker", $event->context["damageTarget"]);
        $this->assertSame("defender", $event->context["reflectionTarget"]);
        $this->assertSame(10, $event->context["damage"]);
        $this->assertEqualsWithDelta(1., $event->context["reflection"], 0.001);
        $this->assertSame("Effect succeeded", $event->context["effectSucceeds"]);
        $this->assertSame("Effect failed", $event->context["effectFails"]);
        $this->assertSame("Effect had no effect", $event->context["noEffect"]);
    }

    public function testIfUsedBuffsAreProperlyExpired()
    {
        $buff1 = $this->createMock(Buff::class);
        $buff1->expects($this->once())->method("consumeRound");
        $buff1->expects($this->once())->method("isExpired")->willReturn(true);
        $buff1->expects($this->once())->method(PropertyHook::get("endMessage"))->willReturn("This buff has expired.");

        $buff2 = $this->createMock(Buff::class);
        $buff2->expects($this->once())->method("consumeRound");
        $buff2->expects($this->once())->method("isExpired")->willReturn(false);

        $buffList = $this->createPartialMock(BuffList::class, ["remove"]);
        $buffList
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("usedBuffs"))
            ->willReturn([
                $buff1,
                $buff2,
            ]);

        $buffList->expects($this->once())->method("remove")->with($buff1);

        $attacker = $this->createStub(FighterInterface::class);
        $defender = $this->createStub(FighterInterface::class);

        $eventList = $buffList->expireOneRound($attacker, $defender);

        $this->assertCount(1, $eventList);
        $this->assertInstanceOf(BuffMessageEvent::class, $eventList[0]);

        $eventList[0]->apply();
        $message = $eventList[0]->decorate();
        $this->assertSame("This buff has expired.", $message->message);
        $this->assertSame($attacker, $message->context["attacker"]);
        $this->assertSame($defender, $message->context["defender"]);
    }
}
