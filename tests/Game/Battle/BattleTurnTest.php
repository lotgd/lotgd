<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\Random\DiceBagInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(BattleTurn::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(DamageEvent::class)]
#[UsesClass(CriticalHitEvent::class)]
class BattleTurnTest extends TestCase
{
    private BattleTurn $battleTurn;
    private MockObject&DiceBagInterface $diceBag;
    private MockObject&BattleState $battleState;
    private MockObject&CurrentCharacterFighter $currentCharacterFighter;
    private MockObject&FighterInterface $fighter;

    protected function setUp(): void
    {
        $this->diceBag = $this->createMock(DiceBagInterface::class);
        $this->battleState = $this->createMock(BattleState::class);
        $this->currentCharacterFighter = $this->createMock(CurrentCharacterFighter::class);
        $this->fighter = $this->createMock(FighterInterface::class);
        
        $this->battleTurn = new BattleTurn($this->diceBag);
    }

    public function testConstantsAreCorrect(): void
    {
        $this->assertSame(0b11, BattleTurn::DamageTurnBoth);
        $this->assertSame(0b01, BattleTurn::DamageTurnGoodGuy);
        $this->assertSame(0b10, BattleTurn::DamageTurnBadGuy);
    }

    public function testPartialTurnWithRiposteEnabledButPositiveDamage(): void
    {
        // Setup
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                false,
                false,
                true,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(5);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        $this->diceBag
            ->expects($this->exactly(2))
            ->method('pseudoBell')
            ->willReturnMap([
                [0, 15, 80],  # Attack roll
                [0, 5, 30], # Defense roll
            ])
        ;

        $this->assertTrue($battleState->isRiposteEnabled);

        // Execute
        $result = $this->battleTurn->partialTurn(
            $battleState,
            $this->currentCharacterFighter,
            $this->fighter
        );

        // Assert
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(1, $result);

        $damageEvent = $result->first();
        $this->assertInstanceOf(DamageEvent::class, $damageEvent);
        $this->assertSame(50, $damageEvent->getDamage()); // 80 - 30 = 50
    }

    public function testPartialTurnWithRiposteEnabledAndNegativeDamage(): void
    {
        // Setup
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                false,
                false,
                true,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(5);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        $this->diceBag
            ->expects($this->exactly(2))
            ->method('pseudoBell')
            ->willReturnMap([
                [0, 15, 30],  # Attack roll
                [0, 5, 80], # Defense roll
            ])
        ;

        $this->assertTrue($battleState->isRiposteEnabled);

        // Execute
        $result = $this->battleTurn->partialTurn(
            $battleState,
            $this->currentCharacterFighter,
            $this->fighter
        );

        // Assert
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(1, $result);

        $damageEvent = $result->first();
        $this->assertInstanceOf(DamageEvent::class, $damageEvent);
        $this->assertSame(-25, $damageEvent->getDamage()); // 30-80 = -50, ripose disabled should put that to -25
    }

    public function testPartialTurnWithRiposteDisabledAndNegativeDamage(): void
    {
        // Setup
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                false,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(5);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        $this->diceBag
            ->expects($this->exactly(2))
            ->method('pseudoBell')
            ->willReturnMap([
                [0, 15, 30],  # Attack roll
                [0, 5, 80], # Defense roll
            ])
        ;

        $this->assertFalse($battleState->isRiposteEnabled);

        // Execute
        $result = $this->battleTurn->partialTurn(
            $battleState,
            $this->currentCharacterFighter,
            $this->fighter
        );

        // Assert
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(1, $result);

        $damageEvent = $result->first();
        $this->assertInstanceOf(DamageEvent::class, $damageEvent);
        $this->assertSame(0, $damageEvent->getDamage()); // 30 - 80 = -50, ripose disabled should put that to 0
    }

    public function testPartialTurnWithCriticalHitsEnabledAndChancesTunedToHaveACriticalHit(): void
    {
        // Setup
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                false,
                true,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(5);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        $this->diceBag
            ->expects($this->exactly(2))
            ->method('pseudoBell')
            ->willReturnMap([
                [0, 45, 80],  # Attack roll
                [0, 5, 10], # Defense roll
            ])
        ;

        $this->diceBag
            ->expects($this->exactly(1))
            ->method("chance")
            ->willReturn(true)
        ;

        $this->assertFalse($battleState->isRiposteEnabled);

        // Execute
        $result = $this->battleTurn->partialTurn(
            $battleState,
            $this->currentCharacterFighter,
            $this->fighter
        );

        // Assert
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);

        $criticalHit = $result->first();
        $this->assertInstanceOf(CriticalHitEvent::class, $criticalHit);

        $damageEvent = $result[1];
        $this->assertInstanceOf(DamageEvent::class, $damageEvent);
        $this->assertSame(70, $damageEvent->getDamage()); // 80 - 10 = 70
    }

    public static function getHalfTurnsOnlyRunsTwoPartialTurnsDataProvider(): array
    {
        return [
            "OnlyRunsTwoPartialTurnsIfOffenseCausesDamage" => [15, 0, 2],
            "OnlyRunsTwoPartialTurnsIfOffenseCausesNegativeDamage" => [-15, 0, 2],
            "OnlyRunsTwoPartialTurnsIfDefenseCausesDamage" => [0, 15, 2],
            "OnlyRunsTwoPartialTurnsIfDefenseCausesNegativeDamage" => [0, -15, 2],
            "OnlyRunsTwoPartialTurnsIfBothCauseDamage" => [15, 15, 2],
            "RunsForPartialTurnsIfFirstTurnCausesNoDamage" => [[0, 15], [0, 15], 4],
            "RunsForPartialTurnsIfFirstTurnCausesNoDamageAndSecondTurnBothCause" => [[0, 15], [0, 0], 4],
            "RunsForPartialTurnsIfFirstTurnCausesNoDamageAndSecondTurnBothOnlyOffenseCauses" => [[0, 15], [0, 0], 4],
            "RunsForPartialTurnsIfFirstTurnCausesNoDamageAndSecondTurnBothOnlyDefenseCauses" => [[0, 0], [0, 15], 4],
        ];
    }

    #[DataProvider("getHalfTurnsOnlyRunsTwoPartialTurnsDataProvider")]
    public function testGetHalfTurns(
        int|array $offenseDamage,
        int|array $defenseDamage,
        int $partialTurnCalls,
    ): void
    {
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                false,
                true,
                false,
            ])
            ->getMock();

        $battleTurn = $this->getMockBuilder(BattleTurn::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->diceBag,
            ])
            ->onlyMethods(["partialTurn"])
            ->getMock();

        $willReturn = [];

        if (is_int($offenseDamage)) {
            $offenseDamageEvents = [
                $this->createMock(CriticalHitEvent::class),
            ];

            $offenseDamageEvents[] = $this->createMock(DamageEvent::class);
            $offenseDamageEvents[count($offenseDamageEvents) - 1]->expects($this->atMost(1))->method("getDamage")->willReturn($offenseDamage);

            $willReturn[] = new ArrayCollection($offenseDamageEvents);
        } else {
            foreach ($offenseDamage as $damage) {
                $offenseDamageEvents = [];
                $offenseDamageEvents[] = $this->createMock(DamageEvent::class);
                $offenseDamageEvents[count($offenseDamageEvents) - 1]->expects($this->atMost(1))->method("getDamage")->willReturn($damage);

                $willReturn[] = new ArrayCollection($offenseDamageEvents);
            }
        }

        if (is_int($defenseDamage)) {
            $defenseDamageEvents = [
                $this->createMock(CriticalHitEvent::class),
            ];

            $defenseDamageEvents[] = $this->createMock(DamageEvent::class);
            $defenseDamageEvents[count($defenseDamageEvents) - 1]->expects($this->atMost(1))->method("getDamage")->willReturn($defenseDamage);

            $willReturn[] = new ArrayCollection($defenseDamageEvents);
        } else {
            foreach ($defenseDamage as $damage) {
                $defenseDamageEvents = [];
                $defenseDamageEvents[] = $this->createMock(DamageEvent::class);
                $defenseDamageEvents[count($defenseDamageEvents) - 1]->expects($this->atMost(1))->method("getDamage")->willReturn($damage);

                $willReturn[] = new ArrayCollection($defenseDamageEvents);
            }
        }

        // $willReturn is now all offense, then all defense. Lets rearrange.
        $resortedWillReturn = [];
        $willReturnLength = count($willReturn);
        for ($i = 0; $i < $willReturnLength / 2; $i++) {
            $resortedWillReturn[] = $willReturn[$i];
            $resortedWillReturn[] = $willReturn[$i + $willReturnLength / 2];
        }

        $willReturn = $resortedWillReturn;

        $battleTurn->expects($this->exactly($partialTurnCalls))->method("partialTurn")->willReturn(... $willReturn);


        $turns = $battleTurn->getHalfTurns($battleState);
        $this->assertSame($willReturn[count($willReturn) - 2], $turns[0]);
        $this->assertSame($willReturn[count($willReturn) - 1], $turns[1]);
    }

    public function testIfCalculateAttackAndDefenseDoesNotAdjustsAttackAndDefenseWithEnabledAdjustmentIfBothHaveSameLevel()
    {
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                true,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(10);

        [$attack, $defense] = $this->battleTurn->calculateAttackAndDefense($battleState, $this->currentCharacterFighter, $this->fighter);

        $this->assertSame(15, $attack);
        $this->assertSame(10, $defense);
    }

    public function testIfCalculateAttackAndDefenseDoesNotAdjustsAttackAndDefenseWithEnabledAdjustmentIfOffenseIsCharacterAndCharacterIsHigher()
    {

        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                true,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        [$attack, $defense] = $this->battleTurn->calculateAttackAndDefense($battleState, $this->currentCharacterFighter, $this->fighter);

        $this->assertSame(15, $attack);
        $this->assertLessThan(10, $defense);
    }


    public function testIfCalculateAttackAndDefenseDoesNotAdjustsAttackAndDefenseWithEnabledAdjustmentIfOffenseIsCharacterAndCharacterIsLower()
    {

        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->currentCharacterFighter,
                $this->fighter,
                true,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(15);

        [$attack, $defense] = $this->battleTurn->calculateAttackAndDefense($battleState, $this->currentCharacterFighter, $this->fighter);

        $this->assertSame(15, $attack);
        $this->assertGreaterThan(10, $defense);
    }

    public function testIfCalculateAttackAndDefenseDoesNotAdjustsAttackAndDefenseWithEnabledAdjustmentIfDefenseIsCharacterAndCharacterIsHigher()
    {

        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->fighter,
                $this->currentCharacterFighter,
                true,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(5);

        [$attack, $defense] = $this->battleTurn->calculateAttackAndDefense($battleState, $this->fighter, $this->currentCharacterFighter);

        $this->assertSame(15, $attack);
        $this->assertGreaterThan(10, $defense);
    }

    public function testIfCalculateAttackAndDefenseDoesNotAdjustsAttackAndDefenseWithEnabledAdjustmentIfDefenseIsCharacterAndCharacterIsLower()
    {

        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->fighter,
                $this->currentCharacterFighter,
                true,
                false,
                false,
            ])
            ->getMock();

        $this->currentCharacterFighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->currentCharacterFighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->currentCharacterFighter->method(PropertyHook::get("level"))->willReturn(10);

        $this->fighter->method(PropertyHook::get("attack"))->willReturn(15);
        $this->fighter->method(PropertyHook::get("defense"))->willReturn(10);
        $this->fighter->method(PropertyHook::get("level"))->willReturn(15);

        [$attack, $defense] = $this->battleTurn->calculateAttackAndDefense($battleState, $this->fighter, $this->currentCharacterFighter);

        $this->assertSame(15, $attack);
        $this->assertLessThan(10, $defense);
    }
}