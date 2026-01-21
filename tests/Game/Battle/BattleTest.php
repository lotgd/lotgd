<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\BattleRoundMessage;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(Battle::class)]
#[UsesClass(Character::class)]
#[UsesClass(Health::class)]
#[UsesClass(Equipment::class)]
#[UsesClass(Stats::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(Fighter::class)]
#[UsesClass(CurrentCharacterFighter::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(Stage::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(BattleMessage::class)]
#[UsesClass(BattleRoundMessage::class)]
#[UsesClass(DeathEvent::class)]
class BattleTest extends KernelTestCase
{
    private Battle $battle;

    private Character $character;
    private BattleTurn $turn;
    private NormalizerInterface&DenormalizerInterface $normalizer;
    private LoggerInterface $logger;

    public function setUp(): void
    {
        $this->normalizer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->character = new Character(name: "Test", level: 1);
        $this->turn = $this->createMock(BattleTurn::class);

        $this->battle = new Battle(
            logger: $this->logger,
            normalizer: $this->normalizer,
            turn: $this->turn,
            character: $this->character,
        );
    }

    public function testStartBattle(): BattleState
    {
        $badGuy = $this->createMock(Fighter::class);

        $battleState = $this->battle->start($badGuy);

        $this->assertInstanceOf(CurrentCharacterFighter::class, $battleState->goodGuy);
        $this->assertInstanceOf(Fighter::class, $battleState->badGuy);

        return $battleState;
    }

    #[Depends("testStartBattle")]
    public function testAddsFightActionWithoutParams(BattleState $battleState): void
    {
        $scene = $this->createMock(Scene::class);
        $stage = new Stage(
            owner: $this->character,
            scene: $scene,
        );

        $this->battle->addFightActions($stage, $scene, $battleState);

        $actionGroups = $stage->actionGroups;
        $this->assertCount(2, $actionGroups);
        $this->assertArrayHasKey(Battle::ActionGroupBattle, $actionGroups);
        $this->assertArrayHasKey(Battle::ActionGroupAutoBattle, $actionGroups);

        $attackActionGroup = $actionGroups[Battle::ActionGroupBattle];
        $actions = $attackActionGroup->getActions();
        $this->assertCount(2, $actions);

        $attackAction = $attackActionGroup->getActionByReference(Battle::FightActionAttack);

        $this->assertNotNull($attackAction);
        // Assert required parameters are set
        $this->assertSame("attack", $attackAction->getParameters()["how"]);
        // Assert battle state is transferred correctly
        $this->assertSame($battleState, $attackAction->getParameters()["battleState"]);

        $attackActionGroup = $actionGroups[Battle::ActionGroupAutoBattle];
        $actions = $attackActionGroup->getActions();
        $this->assertCount(3, $actions);
    }

    #[Depends("testStartBattle")]
    public function testAddsFightActionWithParams(BattleState $battleState): void
    {
        $scene = $this->createMock(Scene::class);
        $stage = new Stage(
            owner: $this->character,
            scene: $scene,
        );
        $params = ["op" => "fight"];

        $this->battle->addFightActions($stage, $scene, $battleState, $params);

        $actionGroups = $stage->actionGroups;
        $this->assertCount(2, $actionGroups);
        $this->assertArrayHasKey(Battle::ActionGroupBattle, $actionGroups);
        $this->assertArrayHasKey(Battle::ActionGroupAutoBattle, $actionGroups);

        $actionGroup = $actionGroups[Battle::ActionGroupAutoBattle];
        $actions = $actionGroup->getActions();
        $this->assertCount(3, $actions);

        $actionGroup = $actionGroups[Battle::ActionGroupBattle];
        $actions = $actionGroup->getActions();
        $this->assertCount(2, $actions);

        $attackAction = $actionGroup->getActionByReference(Battle::FightActionAttack);

        $this->assertNotNull($attackAction);
        // Assert required parameters are set
        $this->assertSame("attack", $actionGroup->getActionByReference(Battle::FightActionAttack)->getParameters()["how"]);
        // Assert battle state is transferred correctly
        $this->assertSame($battleState, $attackAction->getParameters()["battleState"]);
        // Assert custom params are still there
        $this->assertSame("fight", $attackAction->getParameters()["op"]);
    }

    public function testFightOneTurnWithDamageEvents(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);

        $halfTurns = [
            new ArrayCollection([
                $this->createMock(DamageEvent::class),
            ]),
            new ArrayCollection([
                $this->createMock(DamageEvent::class),
            ]),
        ];

        $this->turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $this->battle->fightOneRound($battleState, BattleTurn::DamageTurnBoth);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithDamageEventsForOffenseTurn(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);

        $offenseDamageEvent = $this->createMock(DamageEvent::class);
        $offenseDamageEvent->expects($this->once())->method("apply");
        $offenseDamageEvent->expects($this->once())->method("decorate");

        $defenseDamageEvent = $this->createMock(DamageEvent::class);
        $defenseDamageEvent->expects($this->never())->method("apply");
        $defenseDamageEvent->expects($this->never())->method("decorate");

        $halfTurns = [
            new ArrayCollection([
                $offenseDamageEvent,
            ]),
            new ArrayCollection([
                $defenseDamageEvent,
            ]),
        ];

        $this->turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $this->battle->fightOneRound($battleState, BattleTurn::DamageTurnGoodGuy);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithDamageEventsForDefenseTurn(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);

        $offenseDamageEvent = $this->createMock(DamageEvent::class);
        $offenseDamageEvent->expects($this->never())->method("apply");
        $offenseDamageEvent->expects($this->never())->method("decorate");

        $defenseDamageEvent = $this->createMock(DamageEvent::class);
        $defenseDamageEvent->expects($this->once())->method("apply");
        $defenseDamageEvent->expects($this->once())->method("decorate");

        $halfTurns = [
            new ArrayCollection([
                $offenseDamageEvent,
            ]),
            new ArrayCollection([
                $defenseDamageEvent,
            ]),
        ];

        $this->turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $this->battle->fightOneRound($battleState, BattleTurn::DamageTurnBadGuy);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithNoDamageEvents(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);

        $halfTurns = [
            new ArrayCollection([
            ]),
            new ArrayCollection([
            ]),
        ];

        $this->turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $this->battle->fightOneRound($battleState, BattleTurn::DamageTurnBoth);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testProcessBattleEventsWhenNobodyDies(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);
        $events = [
            $this->createMock(CriticalHitEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(CriticalHitEvent::class),
            $this->createMock(DamageEvent::class),
        ];

        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->once())->method("apply"), $events);
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("decorate"), $events);

        $processedEvents = $this->battle->processBattleEvents(new ArrayCollection($events), $battleState);

        $this->assertSameSize($events, $processedEvents);
        $this->assertSame($events, $processedEvents->toArray());
    }

    public function testProcessBattleEventsWhenGoodGuyDies(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10, 5, 0);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);
        $events = [
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            # After calling $goodGuy->health 3 times, it returns 0. The events after here should not get processed anymore.
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
        ];

        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->once())->method("apply"), array_slice($events, 0, 3));
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("apply"), array_slice($events, 3, 3));
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("decorate"),  $events);

        $processedEvents = $this->battle->processBattleEvents(new ArrayCollection($events), $battleState);
        $lastEntry = $processedEvents->last();

        $this->assertCount(4, $processedEvents);
        $this->assertInstanceOf(DeathEvent::class, $lastEntry);
        $this->assertSame(array_slice($events, 0, 3), array_slice($processedEvents->toArray(), 0, 3));

        $this->assertArrayHasKey("victim", $lastEntry->getContext());
        $this->assertSame($goodGuy, $lastEntry->getContext()["victim"]);
    }

    public function testProcessBattleEventsWhenBadGuyDies(): void
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $goodGuy->method(PropertyHook::get("health"))->willReturn(10);
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("health"))->willReturn(10, 5, 1, 0);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($this->character);
        $events = [
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
            # After calling $badGuy->health 4 times, it returns 0. The events after here should not get processed anymore.
            $this->createMock(DamageEvent::class),
            $this->createMock(DamageEvent::class),
        ];

        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->once())->method("apply"), array_slice($events, 0, 4));
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("apply"), array_slice($events, 4, 2));
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("decorate"),  $events);

        $processedEvents = $this->battle->processBattleEvents(new ArrayCollection($events), $battleState);
        $lastEntry = $processedEvents->last();

        $this->assertCount(5, $processedEvents);
        $this->assertInstanceOf(DeathEvent::class, $processedEvents->last());
        $this->assertSame(array_slice($events, 0, 4), array_slice($processedEvents->toArray(), 0, 4));

        $this->assertArrayHasKey("victim", $lastEntry->getContext());
        $this->assertSame($badGuy, $lastEntry->getContext()["victim"]);
    }
}
