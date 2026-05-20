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
use LotGD2\Game\Battle\BattleEvent\MinionDamageEvent;
use LotGD2\Game\Battle\BattleEvent\RegenerationBuffEvent;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueError;

#[CoversClass(BuffList::class)]
#[UsesClass(LifeTapEvent::class)]
#[UsesClass(DamageReflectionEvent::class)]
#[UsesClass(BattleMessage::class)]
#[UsesClass(BuffMessageEvent::class)]
#[UsesClass(RegenerationBuffEvent::class)]
#[UsesClass(MinionDamageEvent::class)]
class BuffListTest extends TestCase
{
    #[TestWith([-1])]
    #[TestWith([3])]
    #[TestWith([5])]
    #[TestWith([6])]
    #[TestWith([7])]
    #[TestWith([9])]
    #[TestWith([10])]
    #[TestWith([11])]
    #[TestWith([12])]
    #[TestWith([13])]
    #[TestWith([14])]
    #[TestWith([15])]
    public function testIfMultipleActivationsIsImpossible(int $activation)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = new BuffList($logger, $diceBag);

        $logger->expects($this->never())->method("debug");
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Only one type of buff activation is permitted to activate at a time");

        $goodGuy = $this->createStub(FighterInterface::class);
        $badGuy = $this->createStub(FighterInterface::class);

        $buffList->activate($activation, $goodGuy, $badGuy);
    }

    #[TestWith([0])]
    #[TestWith([16])]
    #[TestWith([32])]
    public function testIfMultipleActivationsWithUnknownIsImpossible(int $activation)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = new BuffList($logger, $diceBag);

        $logger->expects($this->never())->method("debug");
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Activation is not on the list of possible activation types ($activation given)");

        $goodGuy = $this->createStub(FighterInterface::class);
        $badGuy = $this->createStub(FighterInterface::class);

        $buffList->activate($activation, $goodGuy, $badGuy);
    }

    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND])]
    #[TestWith([Buff::ACTIVATES_ON_OFFENSE_TURN])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND])]
    public function testIfActivationFailsIfAlreadyActivated(int $activation)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn("GoodGuy");

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("name"))->willReturn("BadGuy");

        $logger->expects($this->never())->method("debug");

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            $activation => [
                // The list what should not matter here.
                1
            ],
        ]);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("You already have activated the buffs for activation type ($activation given).");

        $buffList->activate($activation, $goodGuy, $badGuy);
    }

    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND])]
    #[TestWith([Buff::ACTIVATES_ON_OFFENSE_TURN])]
    #[TestWith([Buff::ACTIVATES_ON_DEFENSE_TURN])]
    public function testIfActivationWorks(int $activation)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn("GoodGuy");

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("name"))->willReturn("BadGuy");

        $logger->expects($this->atLeastOnce())->method("debug");

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["getBuffMessage"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("getBuffMessage")->willReturn(null);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->once())->method("getsActivatedAt")->with($activation)->willReturn(true);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            // We will make sure that, whatever is already in the list, is kept in the list!
            0 => [
                $buff,
            ],
        ]);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("buffs"))->willReturn([$buff]);

        $buffList->expects($this->once())->method(PropertyHook::set("activeBuffs"))->with([
            0 => [
                $buff,
            ],
            $activation => [
                $buff,
            ],
        ]);

        $events = $buffList->activate($activation, $goodGuy, $badGuy);

        $this->assertCount(0, $events);
    }

    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, "Message Round Start"])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND, "Message Round End"])]
    #[TestWith([Buff::ACTIVATES_ON_OFFENSE_TURN, "Message Offense Turn"])]
    #[TestWith([Buff::ACTIVATES_ON_DEFENSE_TURN, "Message Defense Turn"])]
    public function testIfActivationWorksWithMessage(int $activation, string $buffMessage)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn("GoodGuy");

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("name"))->willReturn("BadGuy");

        $logger->expects($this->atLeastOnce())->method("debug");

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["getBuffMessage"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("getBuffMessage")->willReturn($buffMessage);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->once())->method("getsActivatedAt")->with($activation)->willReturn(true);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            // We will make sure that, whatever is already in the list, is kept in the list!
            0 => [
                $buff,
            ],
        ]);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("buffs"))->willReturn([$buff]);

        $buffList->expects($this->once())->method(PropertyHook::set("activeBuffs"))->with([
            0 => [
                $buff,
            ],
            $activation => [
                $buff,
            ],
        ]);

        $events = $buffList->activate($activation, $goodGuy, $badGuy);

        $this->assertCount(1, $events);
    }

    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, "Message Round Start"])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND, "Message Round End"])]
    #[TestWith([Buff::ACTIVATES_ON_OFFENSE_TURN, "Message Offense Turn"])]
    #[TestWith([Buff::ACTIVATES_ON_DEFENSE_TURN, "Message Defense Turn"])]
    public function testIfActivationDoesNotAddBuffIfBuffGetsNotActivatedInThatTurn(int $activation, string $buffMessage)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("name"))->willReturn("GoodGuy");

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("name"))->willReturn("BadGuy");

        $logger->expects($this->atLeastOnce())->method("debug");

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["getBuffMessage"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->never())->method("getBuffMessage");

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->once())->method("getsActivatedAt")->with($activation)->willReturn(false);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            // We will make sure that, whatever is already in the list, is kept in the list!
            0 => [
                $buff,
            ],
        ]);

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("buffs"))->willReturn([$buff]);

        $buffList->expects($this->once())->method(PropertyHook::set("activeBuffs"))->with([
            0 => [
                $buff,
            ],
            $activation => [],
        ]);

        $events = $buffList->activate($activation, $goodGuy, $badGuy);

        $this->assertCount(0, $events);
    }

    public function testGetBuffMessageIsStartMessageIfHasNotBeenUsedYetAndBuffWasNotStartedYet()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["hasBuffBeenUsed"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("hasBuffBeenUsed")->wilLReturn(false);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->once())->method(PropertyHook::get("hasBeenStarted"))->willReturn(false);
        $buff->expects($this->once())->method(PropertyHook::get("startMessage"))->willReturn("Start Message");

        $buffMessage = $buffList->getBuffMessage($buff);

        $this->assertNotNull($buffMessage);
        $this->assertSame("Start Message", $buffMessage);
    }

    public function testGetBuffMessageIsRoundMessageIfHasNotBeenUsedYetAndBuffWasNotStartedYet()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["hasBuffBeenUsed"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("hasBuffBeenUsed")->wilLReturn(false);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->once())->method(PropertyHook::get("hasBeenStarted"))->willReturn(true);
        $buff->expects($this->once())->method(PropertyHook::get("roundMessage"))->willReturn("Round Message");

        $buffMessage = $buffList->getBuffMessage($buff);

        $this->assertNotNull($buffMessage);
        $this->assertSame("Round Message", $buffMessage);
    }

    public function testGetBuffMessageIsNullIfHasBeenUsedYetAndBuffWasNotStartedYet()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["hasBuffBeenUsed"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("hasBuffBeenUsed")->wilLReturn(true);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->never())->method(PropertyHook::get("hasBeenStarted"))->willReturn(false);
        $buff->expects($this->never())->method(PropertyHook::get("startMessage"))->willReturn("Start Message");

        $buffMessage = $buffList->getBuffMessage($buff);

        $this->assertNull($buffMessage);
    }

    public function testGetBuffMessageIsRoundMessageIfHasBeenUsedYetAndBuffWasNotStartedYet()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods(["hasBuffBeenUsed"])
            ->setConstructorArgs([$logger, $diceBag])
            ->getMock();

        $buffList->expects($this->once())->method("hasBuffBeenUsed")->wilLReturn(true);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->never())->method(PropertyHook::get("hasBeenStarted"))->willReturn(true);
        $buff->expects($this->never())->method(PropertyHook::get("roundMessage"))->willReturn("Round Message");

        $buffMessage = $buffList->getBuffMessage($buff);

        $this->assertNull($buffMessage);
    }

    public function testProcessDamageDependentBuffsForBadGuyLifeTap(): void
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

    public function testProcessDamageDependentBuffsForGoodGuyLifeTap(): void
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

    public function testProcessDamageDependentBuffsForBadGuyDamageReflection(): void
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

    public function testProcessDamageDependentBuffsForGoodGuyDamageReflection(): void
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

    public function testIfUsedBuffsAreProperlyExpired(): void
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

    public function testIfRemoveBuffOnlyRemovesSelectedBuff(): void
    {
        $buff1 = $this->createStub(Buff::class);
        $buff2 = $this->createStub(Buff::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug");
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = new BuffList($logger, $diceBag, [$buff1, $buff2]);

        $this->assertCount(2, $buffList->buffs);
        $buffList->remove($buff1);
        $this->assertCount(1, $buffList->buffs);
        $this->assertContains($buff2, $buffList->buffs);
        $this->assertNotContains($buff1, $buffList->buffs);
    }

    public function testIfRemoveBuffOnlySilentlyFailsButLogsIfBuffNotWithinList(): void
    {
        $buff1 = $this->createStub(Buff::class);
        $buff2 = $this->createStub(Buff::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug");
        $diceBag = $this->createStub(DiceBag::class);

        $buffList = new BuffList($logger, $diceBag, [$buff2]);

        $this->assertCount(1, $buffList->buffs);
        $buffList->remove($buff1);
        $this->assertCount(1, $buffList->buffs);
        $this->assertContains($buff2, $buffList->buffs);
        $this->assertNotContains($buff1, $buffList->buffs);
    }

    public function testIfHasBuffBeenUsedReturnsTrueIfBuffIsInUsedList(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);
        $buff1 = $this->createStub(Buff::class);
        $buff2 = $this->createStub(Buff::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();

        $buffList->expects($this->exactly(2))->method(PropertyHook::get("usedBuffs"))->willReturn([
            $buff1,
            $buff2,
        ]);

        $this->assertTrue($buffList->hasBuffBeenUsed($buff1));
        $this->assertTrue($buffList->hasBuffBeenUsed($buff2));
    }

    public function testIfHasBuffBeenUsedReturnsFalseIfBuffIsNotInUsedList(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);
        $buff1 = $this->createStub(Buff::class);
        $buff2 = $this->createStub(Buff::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();

        $buffList->expects($this->exactly(1))->method(PropertyHook::get("usedBuffs"))->willReturn([
            $buff1,
        ]);

        $this->assertFalse($buffList->hasBuffBeenUsed($buff2));
    }

    public function testIfGoodGuyRegenerationCausesRegenerationBuffToBeAdded()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("goodGuyRegeneration"))->willReturn(5);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RegenerationBuffEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("attacker", $buffEvent->getContext()["target"]);
        $this->assertSame($offenseFighter, $buffEvent->target);
    }

    public function testIfBadGuyRegenerationCausesRegenerationBuffToBeAdded()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("badGuyRegeneration"))->willReturn(5);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RegenerationBuffEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("defender", $buffEvent->getContext()["target"]);
        $this->assertSame($defenseFighter, $buffEvent->target);
    }

    public function testMinionsWithNoDamageTowardsEitherParty()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createStub(DiceBag::class);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(1);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(0, $events);
    }

    public function testMinionsWithBadGuyDamage()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->atLeastOnce())->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            $this->assertSame(10, $min);
            $this->assertSame(20, $max);
            return 15;
        });

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(1);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(20);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(0);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("defender", $buffEvent->getContext()["target"]);
        $this->assertSame($defenseFighter, $buffEvent->target);
        $this->assertSame(15, $buffEvent->getContext()["damage"]);
    }

    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([3])]
    #[TestWith([4])]
    #[TestWith([5])]
    #[TestWith([10])]
    #[TestWith([100])]
    public function testMultipleMinionsWithBadGuyDamage(int $amountOfMinions)
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->atLeastOnce())->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            $this->assertSame(10, $min);
            $this->assertSame(20, $max);
            return 15;
        });

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn($amountOfMinions);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(20);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(0);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount($amountOfMinions, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);
    }

    public function testMinionsWithNegativeBadGuyDamage()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->atLeastOnce())->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            $this->assertSame(-10, $min);
            $this->assertSame(-20, $max);
            return -15;
        });

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(1);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(-10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(-20);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(0);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("defender", $buffEvent->getContext()["target"]);
        $this->assertSame($defenseFighter, $buffEvent->target);
        $this->assertSame(-15, $buffEvent->getContext()["damage"]);
    }

    public function testMinionsWithGoodGuyDamage()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->atLeastOnce())->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            $this->assertSame(10, $min);
            $this->assertSame(20, $max);
            return 15;
        });

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(1);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(20);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("attacker", $buffEvent->getContext()["target"]);
        $this->assertSame($offenseFighter, $buffEvent->target);
        $this->assertSame(15, $buffEvent->getContext()["damage"]);
    }

    public function testMinionsWithNegativeGoodGuyDamage()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->atLeastOnce())->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            $this->assertSame(-10, $min);
            $this->assertSame(-20, $max);
            return -15;
        });

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(1);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(0);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(-10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(-20);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);

        $buffEvent = $events[0];

        $this->assertSame("attacker", $buffEvent->getContext()["target"]);
        $this->assertSame($offenseFighter, $buffEvent->target);
        $this->assertSame(-15, $buffEvent->getContext()["damage"]);
    }

    public function testMinionsWithBothGoodGuyDamageAndBadguyDamage()
    {

        $logger = $this->createStub(LoggerInterface::class);
        $diceBag = $this->createMock(DiceBag::class);

        $calls = 0;
        $diceBag->expects($this->exactly(2))->method("pseudoBell")->willReturnCallback(function ($min, $max) use (&$calls) {
            if ($calls === 0) {
                $calls++;

                $this->assertSame(-10, $min);
                $this->assertSame(-20, $max);
                return -15;
            } else {
                $this->assertSame(10, $min);
                $this->assertSame(20, $max);
                return 15;
            }
        });
        $diceBag->expects($this->exactly(2))->method("chance")->willReturn(true, false);

        $buff = $this->createMock(Buff::class);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("numberOfMinions"))->willReturn(2);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinBadGuyDamage"))->willReturn(10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxBadGuyDamage"))->willReturn(20);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMinGoodGuyDamage"))->willReturn(-10);
        $buff->expects($this->atLeastOnce(1))->method(PropertyHook::get("minionMaxGoodGuyDamage"))->willReturn(-20);

        $offenseFighter = $this->createStub(FighterInterface::class);
        $defenseFighter = $this->createStub(FighterInterface::class);

        $buffList = $this
            ->getMockBuilder(BuffList::class)
            ->onlyMethods([])
            ->enableOriginalConstructor()
            ->setConstructorArgs([$logger, $diceBag, []])
            ->getMock();
        ;

        $buffList->expects($this->atLeastOnce())->method(PropertyHook::get("activeBuffs"))->willReturn([
            Buff::ACTIVATES_ON_OFFENSE_TURN => [
                $buff,
            ],
        ]);

        $events = $buffList->processDirectBuffs(Buff::ACTIVATES_ON_OFFENSE_TURN, $offenseFighter, $defenseFighter);

        $this->assertCount(2, $events);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[0]);
        $this->assertInstanceOf(MinionDamageEvent::class, $events[1]);

        $buffEvent = $events[0];

        $this->assertSame("attacker", $buffEvent->getContext()["target"]);
        $this->assertSame($offenseFighter, $buffEvent->target);
        $this->assertSame(-15, $buffEvent->getContext()["damage"]);

        $buffEvent = $events[1];

        $this->assertSame("defender", $buffEvent->getContext()["target"]);
        $this->assertSame($defenseFighter, $buffEvent->target);
        $this->assertSame(15, $buffEvent->getContext()["damage"]);
    }
}
