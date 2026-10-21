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
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Tests\TestTools\HasHandlerRegistryTrait;
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
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(Battle::class)]
#[UsesClass(Character::class)]
#[UsesClass(HealthHandler::class)]
#[UsesClass(EquipmentHandler::class)]
#[UsesClass(StatsHandler::class)]
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
#[UsesClass(BattleNavigationChangeEvent::class)]
class BattleTest extends KernelTestCase
{
    use HasHandlerRegistryTrait;

    private function createCharacter(?string $name = "Test", int $level = 1): Character
    {
        return new Character(name: $name, level: $level);
    }

    /**
     * @param int|int[] $health
     */
    private function createFighter(int|array $health = 10): FighterInterface
    {
        $fighter = $this->createStub(FighterInterface::class);
        if (is_array($health)) {
            $fighter->method(PropertyHook::get("health"))->willReturn(...array_values($health));
        } else {
            $fighter->method(PropertyHook::get("health"))->willReturn($health);
        }

        return $fighter;
    }

    private function createBattle(
        ?LoggerInterface $logger = null,
        ?Stopwatch $stopWatch = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        (DenormalizerInterface&NormalizerInterface)|null $normalizer = null,
        ?BattleTurn $turn = null,
        ?SceneRenderer $sceneRenderer = null,
        ?ActionService $actionService = null,
        ?AttachmentRepository $attachmentRepository = null,
        ?HandlerRegistry $handlerRegistry = null,
        ?DiceBagInterface $diceBag = null,
        ?BuffHandler $buffHandler = null,
    ): Battle {
        $healthHandler = $this->createStub(HealthHandler::class);
        $healthHandler->method("getHealth")->willReturn(10);
        $healthHandler->method("getMaxHealth")->willReturn(10);

        $statsHandler = $this->createStub(StatsHandler::class);
        $statsHandler->method("getLevel")->willReturn(1);
        $statsHandler->method("getTotalAttack")->willReturn(1);
        $statsHandler->method("getTotalDefense")->willReturn(1);

        $equipmentHandler = $this->createStub(EquipmentHandler::class);

        $handlerMap = [
            HealthHandler::class => $healthHandler,
            StatsHandler::class => $statsHandler,
            EquipmentHandler::class => $equipmentHandler,
            BuffHandler::class => $buffHandler ?? $this->createStub(BuffHandler::class),
        ];

        return new Battle(
            logger: $logger ?? $this->createStub(LoggerInterface::class),
            stopWatch: $stopWatch ?? $this->createStub(Stopwatch::class),
            eventDispatcher: $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            normalizer: $normalizer ?? new Serializer([new ObjectNormalizer()], [new JsonEncoder()]),
            turn: $turn ?? $this->createStub(BattleTurn::class),
            sceneRenderer: $sceneRenderer ?? $this->createStub(SceneRenderer::class),
            actionService: $actionService ?? $this->createStub(ActionService::class),
            attachmentRepository: $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
            diceBag: $diceBag ?? $this->createStub(DiceBagInterface::class),
            handlerRegistry: $handlerRegistry ?? $this->createHandlerRegistry($handlerMap),
            buffHandler: $buffHandler ?? $this->createStub(BuffHandler::class),
        );
    }

    private function createBattleState(
        ?FighterInterface $goodGuy = null,
        ?FighterInterface $badGuy = null,
        ?Character $character = null,
    ): BattleState {
        $character ??= $this->createCharacter();
        $goodGuy ??= $this->createFighter(10);
        $badGuy ??= $this->createFighter(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
        );
        $battleState->setCharacter($character);

        return $battleState;
    }

    public function testStartBattle(): BattleState
    {
        $character = $this->createCharacter();
        $battle = $this->createBattle();
        $badGuy = new Fighter(name: null, level: null, weapon: null, health: null, attack: null, defense: null, kwargs: []);

        $battleState = $battle->start(
            $character,
            $badGuy,
            gameStateService: $this->createStub(GameStateService::class),
        );

        $this->assertInstanceOf(CurrentCharacterFighter::class, $battleState->goodGuy);
        $this->assertInstanceOf(Fighter::class, $battleState->badGuy);

        return $battleState;
    }

    #[Depends("testStartBattle")]
    public function testAddsFightActionWithoutParams(BattleState $battleState): void
    {
        $character = $battleState->character ?? $this->createCharacter();
        $scene = $this->createStub(Scene::class);
        $stage = new Stage(
            owner: $character,
            scene: $scene,
        );

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(function (BattleNavigationChangeEvent $event, string $eventName) {
            $this->assertSame(Battle::OnAddFightActions, $eventName);
            return $event;
        });

        $battle = $this->createBattle(
            eventDispatcher: $eventDispatcher,
        );

        $battle->addFightActions($stage, $scene, $battleState, $character);

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
        $character = $battleState->character ?? $this->createCharacter();
        $scene = $this->createStub(Scene::class);
        $stage = new Stage(
            owner: $character,
            scene: $scene,
        );
        $params = ["op" => "fight"];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(function (BattleNavigationChangeEvent $event, string $eventName) {
            $this->assertSame(Battle::OnAddFightActions, $eventName);
            return $event;
        });

        $battle = $this->createBattle(
            eventDispatcher: $eventDispatcher,
        );

        $battle->addFightActions($stage, $scene, $battleState, $character, $params);

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
        $character = $this->createStub(Character::class);
        $battleState = $this->createBattleState();
        $turn = $this->createMock(BattleTurn::class);

        $halfTurns = [
            new ArrayCollection([
                $this->createStub(DamageEvent::class),
            ]),
            new ArrayCollection([
                $this->createStub(DamageEvent::class),
            ]),
        ];

        $turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $battle = $this->createBattle(turn: $turn);
        $battle->fightOneRound($character, $battleState, BattleTurn::DamageTurnBoth);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithDamageEventsForOffenseTurn(): void
    {
        $character = $this->createStub(Character::class);
        $battleState = $this->createBattleState();
        $turn = $this->createMock(BattleTurn::class);

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

        $turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $battle = $this->createBattle(turn: $turn);
        $battle->fightOneRound($character, $battleState, BattleTurn::DamageTurnGoodGuy);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithDamageEventsForDefenseTurn(): void
    {
        $character = $this->createStub(Character::class);
        $battleState = $this->createBattleState();
        $turn = $this->createMock(BattleTurn::class);

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

        $turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $battle = $this->createBattle(turn: $turn);
        $battle->fightOneRound($character, $battleState, BattleTurn::DamageTurnBadGuy);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testFightOneTurnWithNoDamageEvents(): void
    {
        $character = $this->createStub(Character::class);
        $battleState = $this->createBattleState();
        $turn = $this->createMock(BattleTurn::class);

        $halfTurns = [
            new ArrayCollection([
            ]),
            new ArrayCollection([
            ]),
        ];

        $turn->expects($this->once())->method("getHalfTurns")->willReturn($halfTurns);

        $battle = $this->createBattle(turn: $turn);
        $battle->fightOneRound($character, $battleState, BattleTurn::DamageTurnBoth);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testProcessBattleEventsWhenNobodyDies(): void
    {
        $battleState = $this->createBattleState();
        $events = [
            $this->createMock(CriticalHitEvent::class),
            $this->createMock(DamageEvent::class),
            $this->createMock(CriticalHitEvent::class),
            $this->createMock(DamageEvent::class),
        ];

        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->once())->method("apply"), $events);
        array_map(fn (MockObject&BattleEventInterface $o) => $o->expects($this->never())->method("decorate"), $events);

        $battle = $this->createBattle();
        $processedEvents = $battle->processBattleEvents(new ArrayCollection($events), $battleState);

        $this->assertSameSize($events, $processedEvents);
        $this->assertSame($events, $processedEvents->toArray());
    }

    public function testProcessBattleEventsWhenGoodGuyDies(): void
    {
        $goodGuy = $this->createFighter([10, 5, 0]);
        $battleState = $this->createBattleState(goodGuy: $goodGuy);
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

        $battle = $this->createBattle();
        $processedEvents = $battle->processBattleEvents(new ArrayCollection($events), $battleState);
        $lastEntry = $processedEvents->last();

        $this->assertCount(4, $processedEvents);
        $this->assertInstanceOf(DeathEvent::class, $lastEntry);
        $this->assertSame(array_slice($events, 0, 3), array_slice($processedEvents->toArray(), 0, 3));

        $this->assertArrayHasKey("victim", $lastEntry->getContext());
        $this->assertSame($goodGuy, $lastEntry->getContext()["victim"]);
    }

    public function testProcessBattleEventsWhenBadGuyDies(): void
    {
        $badGuy = $this->createFighter([10, 5, 1, 0]);
        $battleState = $this->createBattleState(badGuy: $badGuy);
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

        $battle = $this->createBattle();
        $processedEvents = $battle->processBattleEvents(new ArrayCollection($events), $battleState);
        $lastEntry = $processedEvents->last();

        $this->assertCount(5, $processedEvents);
        $this->assertInstanceOf(DeathEvent::class, $processedEvents->last());
        $this->assertSame(array_slice($events, 0, 4), array_slice($processedEvents->toArray(), 0, 4));

        $this->assertArrayHasKey("victim", $lastEntry->getContext());
        $this->assertSame($badGuy, $lastEntry->getContext()["victim"]);
    }
}
