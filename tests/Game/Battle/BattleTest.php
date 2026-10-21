<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\BattleRoundMessage;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Event\BattleSkillActivationEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\CriticalHitEvent;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
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
use Psr\Log\NullLogger;
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
#[UsesClass(BuffList::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Attachment::class)]
#[UsesClass(BattleAttachment::class)]
#[UsesClass(SimpleStageParameterEvent::class)]
#[UsesClass(BattleSkillActivationEvent::class)]
#[UsesClass(StageChangeEvent::class)]
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

        if ($buffHandler === null) {
            $buffHandler = $this->createStub(BuffHandler::class);
            $buffHandler->method("getBuffList")->willReturn(new BuffList(
                new NullLogger(),
                $diceBag ?? $this->createStub(DiceBagInterface::class),
                []
            ));
        }

        $handlerMap = [
            HealthHandler::class => $healthHandler,
            StatsHandler::class => $statsHandler,
            EquipmentHandler::class => $equipmentHandler,
            BuffHandler::class => $buffHandler,
        ];

        if ($attachmentRepository === null) {
            $attachmentRepository = $this->createStub(AttachmentRepository::class);
            $attachmentRepository->method("__call")
                ->willReturn(new Attachment(name: "Battle Attachment", attachmentClass: BattleAttachment::class));
        }

        if ($eventDispatcher === null) {
            $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
            $eventDispatcher->method("dispatch")->willReturnArgument(0);
        }

        if ($turn === null) {
            $turn = $this->createStub(BattleTurn::class);
            $turn->method("getHalfTurns")->willReturn([
                new ArrayCollection([]),
                new ArrayCollection([]),
            ]);
        }

        return new Battle(
            logger: $logger ?? $this->createStub(LoggerInterface::class),
            stopWatch: $stopWatch ?? $this->createStub(Stopwatch::class),
            eventDispatcher: $eventDispatcher,
            normalizer: $normalizer ?? new Serializer([new ObjectNormalizer()], [new JsonEncoder()]),
            turn: $turn,
            sceneRenderer: $sceneRenderer ?? $this->createStub(SceneRenderer::class),
            actionService: $actionService ?? $this->createStub(ActionService::class),
            attachmentRepository: $attachmentRepository,
            diceBag: $diceBag ?? $this->createStub(DiceBagInterface::class),
            handlerRegistry: $handlerRegistry ?? $this->createHandlerRegistry($handlerMap),
        );
    }

    private function createBattleState(
        ?FighterInterface $goodGuy = null,
        ?FighterInterface $badGuy = null,
        ?Character $character = null,
        bool $isLevelAdjustmentEnabled = true,
        bool $isCriticalHitEnabled = true,
        bool $isRiposteEnabled = true,
        bool $allowFlee = true,
    ): BattleState {
        $character ??= $this->createCharacter();
        $goodGuy ??= $this->createFighter(10);
        $badGuy ??= $this->createFighter(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $badGuy,
            isLevelAdjustmentEnabled: $isLevelAdjustmentEnabled,
            isCriticalHitEnabled: $isCriticalHitEnabled,
            isRiposteEnabled: $isRiposteEnabled,
            allowFlee: $allowFlee,
        );
        $battleState->setCharacter($character);

        return $battleState;
    }

    /**
     * @param array<string, mixed> $actionParams
     */
    private function createStageChangeEvent(
        mixed $battleState = null,
        ?Character $character = null,
        array $actionParams = [],
        ?Scene $scene = null,
        ?Stage $stage = null,
    ): StageChangeEvent {
        $character ??= $this->createCharacter();
        $scene ??= $this->createStub(Scene::class);
        $stage ??= new Stage(owner: $character, scene: $scene);

        if ($battleState !== null) {
            $actionParams["battleState"] = $battleState;
        }

        $action = new Action($scene, "ActionName", $actionParams);

        return new StageChangeEvent($stage, $action, $scene);
    }

    /**
     * @param array<string, mixed> $actionParams
     */
    private function createSimpleStageParameterEvent(array $actionParams = []): SimpleStageParameterEvent
    {
        $character = $this->createCharacter();
        $scene = $this->createStub(Scene::class);
        $stage = new Stage(owner: $character, scene: $scene);
        $action = new Action($scene, "ActionName", $actionParams);

        return new SimpleStageParameterEvent($stage, $action, $scene);
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

    public function testStartBattleThrowsExceptionWhenNormalizationFails(): void
    {
        $character = $this->createCharacter();
        $normalizer = $this->createStub(Serializer::class);
        $normalizer->method("normalize")->willReturn("invalid");

        $battle = $this->createBattle(normalizer: $normalizer);
        $badGuy = new Fighter(name: null, level: null, weapon: null, health: null, attack: null, defense: null, kwargs: []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Badguy doesn't normalize to an array.");

        $battle->start($character, $badGuy, gameStateService: $this->createStub(GameStateService::class));
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

    public function testAddsFightActionsWithoutFleeWhenFleeNotAllowed(): void
    {
        $character = $this->createCharacter();
        $scene = $this->createStub(Scene::class);
        $stage = new Stage(
            owner: $character,
            scene: $scene,
        );
        $battleState = $this->createBattleState(character: $character, allowFlee: false);

        $battle = $this->createBattle();
        $battle->addFightActions($stage, $scene, $battleState, $character);

        $actionGroups = $stage->actionGroups;
        $attackActionGroup = $actionGroups[Battle::ActionGroupBattle];

        $this->assertNull($attackActionGroup->getActionByReference(Battle::FightActionFlee));
        $this->assertNotNull($attackActionGroup->getActionByReference(Battle::FightActionAttack));
        $this->assertCount(1, $attackActionGroup->getActions());
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
        $this->assertSame(BattleStateStatusEnum::BadGuyWon, $battleState->result);
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
        $this->assertSame(BattleStateStatusEnum::GoodGuyWon, $battleState->result);
    }

    public function testOnBeforeSameSceneChangeIgnoredWhenNoBattleState(): void
    {
        $event = $this->createStageChangeEvent(battleState: null);
        $battle = $this->createBattle();

        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnBeforeSameSceneChangeWhenAttachmentNotFound(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(battleState: $battleState);

        $attachmentRepo = $this->createStub(AttachmentRepository::class);
        $attachmentRepo->method("__call")
            ->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("critical")->with("Attachment not found.");

        $battle = $this->createBattle(logger: $logger, attachmentRepository: $attachmentRepo);
        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnBeforeSameSceneChangeRegularAttackContinuesFight(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => "attack",
            ],
        );

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->atLeastOnce())->method("resetActionGroups");

        $battle = $this->createBattle(actionService: $actionService);
        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertCount(1, $event->stage->paragraphs);
        $this->assertArrayHasKey("lotgd2.paragraph.DefaultFightTrait.FightMessage", $event->stage->paragraphs);
        $this->assertCount(1, $event->stage->attachments);
        $this->assertSame(1, $battleState->roundCounter);
        $this->assertArrayHasKey(Battle::ActionGroupBattle, $event->stage->actionGroups);
    }

    public function testOnBeforeSameSceneChangeBattleEndsWhenBadGuyDefeated(): void
    {
        $badGuy = $this->createFighter(0);
        $battleState = $this->createBattleState(badGuy: $badGuy);
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => "attack",
            ],
        );

        $sceneRenderer = $this->createMock(SceneRenderer::class);
        $sceneRenderer->expects($this->once())->method("addActions")->with($event->stage, $event->scene);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method("dispatch")->willReturnCallback(function ($ev, ?string $eventName = null) {
            $this->assertSame(Battle::OnFightEnds, $eventName);
            return $ev;
        });

        $turn = $this->createStub(BattleTurn::class);
        $turn->method("getHalfTurns")->willReturn([
            new ArrayCollection([$this->createStub(DamageEvent::class)]),
            new ArrayCollection([]),
        ]);

        $battle = $this->createBattle(
            eventDispatcher: $eventDispatcher,
            turn: $turn,
            sceneRenderer: $sceneRenderer,
        );
        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertTrue($battleState->isOver());
        $this->assertSame(BattleStateStatusEnum::GoodGuyWon, $battleState->result);
    }

    public function testOnBeforeSameSceneChangeSurpriseAttackFirstRound(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => "attack",
                Battle::SurpriseActionParam => true,
            ],
        );

        $offenseDamage = $this->createMock(DamageEvent::class);
        $offenseDamage->expects($this->once())->method("apply");

        $defenseDamage = $this->createMock(DamageEvent::class);
        $defenseDamage->expects($this->never())->method("apply");

        $turn = $this->createMock(BattleTurn::class);
        $turn->expects($this->once())->method("getHalfTurns")->willReturn([
            new ArrayCollection([$offenseDamage]),
            new ArrayCollection([$defenseDamage]),
        ]);

        $battle = $this->createBattle(turn: $turn);
        $battle->onBeforeSameSceneChange($event);

        $this->assertSame(1, $battleState->roundCounter);
    }

    public function testOnBeforeSameSceneChangeMultipleRounds(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => "attack",
                Battle::RoundsActionParam => 3,
            ],
        );

        $turn = $this->createMock(BattleTurn::class);
        $turn->expects($this->exactly(3))->method("getHalfTurns")->willReturn([
            new ArrayCollection([]),
            new ArrayCollection([]),
        ]);

        $battle = $this->createBattle(turn: $turn);
        $battle->onBeforeSameSceneChange($event);

        $this->assertSame(3, $battleState->roundCounter);
    }

    public function testOnBeforeSameSceneChangeSkillActivation(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => Battle::SkillFightActionParamValue,
                Battle::SkillActionParam => "fireball",
            ],
        );

        $dispatchedEvents = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())->method("dispatch")->willReturnCallback(function ($ev, ?string $eventName = null) use (&$dispatchedEvents) {
            if ($eventName !== null) {
                $dispatchedEvents[$eventName] = $ev;
            }
            return $ev;
        });

        $battle = $this->createBattle(eventDispatcher: $eventDispatcher);
        $battle->onBeforeSameSceneChange($event);

        $this->assertArrayHasKey(Battle::OnSkillActivationEvent, $dispatchedEvents);
        $skillEvent = $dispatchedEvents[Battle::OnSkillActivationEvent];
        $this->assertInstanceOf(BattleSkillActivationEvent::class, $skillEvent);
        $this->assertSame("fireball", $skillEvent->skillName);
    }

    public function testOnBeforeSameSceneChangeFleeSuccess(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => Battle::FleeFightActionParamValue,
            ],
        );

        $dispatchedEvents = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))->method("dispatch")->willReturnCallback(function (SimpleStageParameterEvent $ev, ?string $eventName = null) use (&$dispatchedEvents) {
            if ($eventName !== null) {
                $dispatchedEvents[] = $eventName;
            }
            if ($eventName === Battle::OnFightFlee) {
                $ev->params["success"] = true;
            }
            return $ev;
        });

        $battle = $this->createBattle(eventDispatcher: $eventDispatcher);
        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertSame([Battle::OnFightFlee, Battle::OnFightFled], $dispatchedEvents);
        $this->assertSame(0, $battleState->roundCounter);
    }

    public function testOnBeforeSameSceneChangeFleeFailedWithPunishment(): void
    {
        $battleState = $this->createBattleState();
        $event = $this->createStageChangeEvent(
            battleState: $battleState,
            actionParams: [
                Battle::FightActionParam => Battle::FleeFightActionParamValue,
            ],
        );

        $dispatchedEvents = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())->method("dispatch")->willReturnCallback(function ($ev, ?string $eventName = null) use (&$dispatchedEvents) {
            if ($eventName !== null) {
                $dispatchedEvents[] = $eventName;
            }
            if ($eventName === Battle::OnFightFlee && $ev instanceof SimpleStageParameterEvent) {
                $ev->params["success"] = false;
                $ev->params["punishment"] = true;
            }
            return $ev;
        });

        $offenseDamage = $this->createMock(DamageEvent::class);
        $offenseDamage->expects($this->never())->method("apply");

        $defenseDamage = $this->createMock(DamageEvent::class);
        $defenseDamage->expects($this->once())->method("apply");

        $turn = $this->createMock(BattleTurn::class);
        $turn->expects($this->once())->method("getHalfTurns")->willReturn([
            new ArrayCollection([$offenseDamage]),
            new ArrayCollection([$defenseDamage]),
        ]);

        $battle = $this->createBattle(
            eventDispatcher: $eventDispatcher,
            turn: $turn,
        );
        $result = $battle->onBeforeSameSceneChange($event);

        $this->assertSame($event, $result);
        $this->assertSame(1, $battleState->roundCounter);
        $this->assertContains(Battle::OnFightFlee, $dispatchedEvents);
        $this->assertNotContains(Battle::OnFightFled, $dispatchedEvents);
    }

    public function testOnFightFleeWhenSurpriseIsTrue(): void
    {
        $event = $this->createSimpleStageParameterEvent([Battle::SurpriseActionParam => true]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("critical")->with("Successfully escaped from the enemy.");

        $battle = $this->createBattle(logger: $logger);
        $result = $battle->onFightFlee($event);

        $this->assertTrue($result->params["success"]);
    }

    public function testOnFightFleeWhenDiceBagChanceSucceeds(): void
    {
        $event = $this->createSimpleStageParameterEvent();

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->with(0.3333, precision: 4)->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("critical")->with("Successfully escaped from the enemy.");

        $battle = $this->createBattle(logger: $logger, diceBag: $diceBag);
        $result = $battle->onFightFlee($event);

        $this->assertTrue($result->params["success"]);
    }

    public function testOnFightFleeWhenDiceBagChanceFails(): void
    {
        $event = $this->createSimpleStageParameterEvent();

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->with(0.3333, precision: 4)->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method("critical");

        $battle = $this->createBattle(logger: $logger, diceBag: $diceBag);
        $result = $battle->onFightFlee($event);

        $this->assertFalse($result->params["success"]);
        $this->assertTrue($result->params["punishment"]);
    }
}
