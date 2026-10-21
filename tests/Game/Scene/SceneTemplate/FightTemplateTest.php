<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\WealthHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Game\Scene\SpecialService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\CreatureRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(FightTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(BattleState::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(HandlerRegistry::class)]
class FightTemplateTest extends TestCase
{
    private function getDefaultTemplateConfig(): array
    {
        return [
            'searchFightAction' => 'Search for a fight',
            'searchSlummingAction' => 'Go slumming',
            'searchThrillseekingAction' => 'Go thrillseeking',
        ];
    }

    /**
     * @param string[][]|"never"|null $actionMap
     * @param array<string, mixed>|bool $templateConfig
     * @return array{Stage&MockObject, Action&MockObject, Scene&MockObject, Character&MockObject}
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function getStageMocks(
        int $characterId = 123,
        int $characterLevel = 5,
        null|array|string $actionMap = null,
        bool|array $templateConfig = true,
        string $sceneTitle = 'Forest',
    ): array {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        if ($actionMap === 'never') {
            $action->expects($this->never())
                ->method('getParameter');
        } elseif (is_array($actionMap)) {
            $action->expects($this->atLeastOnce())
                ->method('getParameter')
                ->willReturnMap($actionMap);
        }

        if ($templateConfig === false) {
            $scene->expects($this->never())
                ->method(PropertyHook::get("templateConfig"));
        } else {
            $config = is_array($templateConfig) ? $templateConfig : $this->getDefaultTemplateConfig();
            $scene->expects($this->atLeastOnce())
                ->method(PropertyHook::get("templateConfig"))
                ->willReturn($config);
        }

        $scene->method(PropertyHook::get("title"))
            ->willReturn($sceneTitle);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $character->method(PropertyHook::get("id"))
            ->willReturn($characterId);

        $character->method(PropertyHook::get("level"))
            ->willReturn($characterLevel);

        return [$stage, $action, $scene, $character];
    }

    private function getStubbedHandlerRegistry(
        ?StatsHandler $statsHandler = null,
        ?WealthHandler $wealthHandler = null,
        ?HealthHandler $healthHandler = null,
    ): HandlerRegistry {
        $handlerRegistry = $this->createStub(HandlerRegistry::class);
        $handlerRegistry->method("get")
            ->willReturnMap([
                [StatsHandler::class, $statsHandler ?? $this->createStub(StatsHandler::class)],
                [WealthHandler::class, $wealthHandler ?? $this->createStub(WealthHandler::class)],
                [HealthHandler::class, $healthHandler ?? $this->createStub(HealthHandler::class)],
            ]);
        return $handlerRegistry;
    }

    /**
     * @param string[] $onlyMethods
     * @return ($onlyMethods is non-empty-array ? ($returnAsMock is true ? FightTemplate&MockObject : FightTemplate&Stub) : FightTemplate)
     */
    private function getPartialTemplate(
        array $onlyMethods = [],
        bool $returnAsMock = true,
        ?Security $security = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?AttachmentRepository $attachmentRepository = null,
        ?DiceBagInterface $diceBag = null,
        ?CreatureRepository $creatureRepository = null,
        ?Battle $battle = null,
        ?HandlerRegistry $handlerRegistry = null,
        ?StatsHandler $stats = null,
        ?WealthHandler $gold = null,
        ?HealthHandler $health = null,
        ?SpecialService $specialService = null,
        ?GameStateService $gameStateService = null,
    ): FightTemplate {
        if ($handlerRegistry === null) {
            $handlerRegistry = $this->getStubbedHandlerRegistry($stats, $gold, $health);
        }

        $constructorArgs = [
            $security ?? $this->createStub(Security::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
            $diceBag ?? $this->createStub(DiceBagInterface::class),
            $creatureRepository ?? $this->createStub(CreatureRepository::class),
            $battle ?? $this->createStub(Battle::class),
            $handlerRegistry,
            $specialService ?? $this->createStub(SpecialService::class),
            $gameStateService ?? $this->createStub(GameStateService::class),
        ];

        if (empty($onlyMethods)) {
            return new FightTemplate(...$constructorArgs);
        }

        if ($returnAsMock) {
            $stubOrMock = $this->getMockBuilder(FightTemplate::class);
        } else {
            $stubOrMock = $this->getStubBuilder(FightTemplate::class);
        }

        $stubOrMock = $stubOrMock->onlyMethods($onlyMethods)
            ->setConstructorArgs($constructorArgs);

        if ($returnAsMock) {
            return $stubOrMock->getMock();
        } else {
            return $stubOrMock->getStub();
        }
    }

    public function testOnSceneChangeWithDefaultAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 5,
        );

        $action->expects($this->once())
            ->method('getParameter')
            ->with('op')
            ->willReturn('unknown');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    'Called FightTemplate::onSceneChange, op=unknown' => true,
                    'Called FightTemplate::defaultAction' => true,
                    default => throw new \Exception(),
                };
            });

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->atLeastOnce())
            ->method('isAlive')
            ->willReturn(true);

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->onSceneChange();
    }

    public function testOnSceneChangeWithSearchAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks();
        $creature = $this->createMock(Creature::class);
        $attachment = $this->createMock(Attachment::class);
        $battleStateGoodGuy = $this->createMock(FighterInterface::class);
        $battleStateBadGuy = $this->createMock(FighterInterface::class);
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$battleStateGoodGuy, $battleStateBadGuy])
            ->getMock();

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'search'],
                ['level', 0, 2],
            ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('debug');

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $health->expects($this->once())
            ->method('decrementTurns')
            ->willReturn($health);

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(4))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, false, false, false); // No special, No random level change, no surprise attack

        $creatureRepository = $this->createMock(CreatureRepository::class);
        $creatureRepository->expects($this->once())
            ->method('getRandomCreature')
            ->with(7) // base level 5 + action level 2 = 7
            ->willReturn($creature);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("name"))
            ->willReturn("Goblin");

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(7);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("health"))
            ->willReturn(50);

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn($attachment);

        $battle = $this->createMock(Battle::class);
        $battle->expects($this->once())
            ->method('start')
            ->with($character, $creature)
            ->willReturn($battleState);

        $stage->expects($this->once())
            ->method('addAttachment')
            ->with($attachment);

        $stage->expects($this->once())
            ->method('clearActionGroups');

        $battle->expects($this->once())
            ->method('addFightActions')
            ->with($stage, $scene, $battleState, $character, ['op' => 'fight', 'surprise' => true]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $value) use ($battleStateBadGuy) {
                $this->assertSame("lotgd2.paragraph.fightTemplate.fightCreatureIsSurprised", $value[0]->id);
                $this->assertStringContainsString("badGuy.name", $value[0]->text);
                $this->assertStringContainsString("badGuy.weapon", $value[0]->text);
                $this->assertStringStartsWith("You walk through the forest, looking for a monster to fight against. After a", $value[0]->text);
                $this->assertArrayHasKey("badGuy", $value[0]->context);
                $this->assertSame($battleStateBadGuy, $value[0]->context["badGuy"]);
            });

        $fightTemplate = $this->getPartialTemplate(
            logger: $logger,
            attachmentRepository: $attachmentRepository,
            diceBag: $diceBag,
            creatureRepository: $creatureRepository,
            battle: $battle,
            stats: $stats,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->onSceneChange();
    }

    public function testOnSceneChangeWithSearchActionWithCharacterGettingSurprised(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks();
        $creature = $this->createMock(Creature::class);
        $attachment = $this->createMock(Attachment::class);
        $battleStateGoodGuy = $this->createMock(FighterInterface::class);
        $battleStateBadGuy = $this->createMock(FighterInterface::class);
        $battleState = $this->getMockBuilder(BattleState::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$battleStateGoodGuy, $battleStateBadGuy])
            ->getMock();

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'search'],
                ['level', 0, 2],
            ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('debug');

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(4))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, false, false, true); // No special, No random level change, surprise attack

        $creatureRepository = $this->createMock(CreatureRepository::class);
        $creatureRepository->expects($this->once())
            ->method('getRandomCreature')
            ->with(7) // base level 5 + action level 2 = 7
            ->willReturn($creature);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("name"))
            ->willReturn("Goblin");

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(7);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("health"))
            ->willReturn(50);

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn($attachment);

        $battle = $this->createMock(Battle::class);
        $battle->expects($this->once())
            ->method('start')
            ->with($character, $creature)
            ->willReturn($battleState);

        $stage->expects($this->once())
            ->method('addAttachment')
            ->with($attachment);

        $stage->expects($this->once())
            ->method('clearActionGroups');

        $battle->expects($this->once())
            ->method('addFightActions')
            ->with($stage, $scene, $battleState, $character, ['op' => 'fight']);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $value) use ($battleStateBadGuy) {
                $this->assertSame("lotgd2.paragraph.fightTemplate.fightCharacterIsSurprised", $value[0]->id);
                $this->assertStringContainsString("badGuy.name", $value[0]->text);
                $this->assertStringContainsString("badGuy.weapon", $value[0]->text);
                $this->assertStringStartsWith("You walk through the forest, looking for a monster to fight against. Suddenly", $value[0]->text);
                $this->assertArrayHasKey("badGuy", $value[0]->context);
                $this->assertSame($battleStateBadGuy, $value[0]->context["badGuy"]);
            });

        $fightTemplate = $this->getPartialTemplate(
            logger: $logger,
            attachmentRepository: $attachmentRepository,
            diceBag: $diceBag,
            creatureRepository: $creatureRepository,
            battle: $battle,
            stats: $stats,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->onSceneChange();
    }

    public function testOnSceneChangeWithFightAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            templateConfig: false,
        );

        $action->expects($this->atLeastOnce())
            ->method('getParameter')
            ->willReturnMap([
                ["op", null, "fight"],
                ["how", null, null],
            ]);

        $found = false;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->willReturnCallback(function (string $message, array $context) use (&$found) {
                if ($message === 'Called FightTemplate::onSceneChange, op=fight') {
                    $found = true;
                }
            });

        $fightTemplate = $this->getPartialTemplate(
            logger: $logger,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->onSceneChange();

        $this->assertTrue($found);
    }

    public function testOnSceneChangeWithCheats(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 1,
        );

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'cheat'],
                ['what', null, 'experience'],
            ]);

        $security = $this->createMock(Security::class);
        $security->expects($this->atLeastOnce())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('addExperience')
            ->with($character, 1000);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->withAnyParameters();

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->atLeastOnce())
            ->method('isAlive')
            ->willReturn(true);

        $stage->expects($this->exactly(2))
            ->method('addActionGroup');

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            stats: $stats,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->onSceneChange();
    }

    public function testDefaultActionWhenDead(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            templateConfig: false,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called FightTemplate::defaultAction');

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->atLeastOnce())
            ->method('isAlive')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method('addParagraph')
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.fightTemplate.isDeadMessage", $paragraph->id);
                $this->assertSame("You are dead. You can't fight any more battles today.", $paragraph->text);
                return $stage;
            });

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->defaultAction();
    }

    public function testSearchActionWhenTired(): void
    {
        $templateConfig = [
            'searchFightAction' => 'Search for a fight',
        ];
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 1,
            templateConfig: $templateConfig,
        );

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('getTurns')
            ->willReturn(0);

        $health->expects($this->never())
            ->method('decrementTurns');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called FightTemplate::defaultAction');

        $health->expects($this->atLeastOnce())
            ->method('isAlive')
            ->willReturn(true);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertSame("lotgd2.paragraph.fightTemplate.tooTired", $paragraphs[0]->id);
                $this->assertSame("You are too tired to search the forest any longer today. Perhaps tomorrow you will have more energy.", $paragraphs[0]->text);
                return $stage;
            });

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->searchAction();
    }

    public function testSearchActionNoCreatureFound(): void
    {
        $templateConfig = [
            'searchFightAction' => 'Search for a fight',
        ];
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterId: 123,
            characterLevel: 5,
            templateConfig: $templateConfig,
        );

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);
        $health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $action->expects($this->exactly(1))
            ->method('getParameter')
            ->with('level', 0)
            ->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug');
        $logger->expects($this->once())
            ->method('critical')
            ->with('Character 123 did not find any creatures');

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(3))
            ->method('chance')
            ->willReturn(false);

        $creatureRepository = $this->createMock(CreatureRepository::class);
        $creatureRepository->expects($this->once())
            ->method('getRandomCreature')
            ->willReturn(null);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $value) {
                $this->assertSame('This place looks very peaceful.', $value[0]->text);
            });

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            diceBag: $diceBag,
            creatureRepository: $creatureRepository,
            stats: $stats,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->searchAction();
    }

    public function testSearchActionNoAttachment(): void
    {
        $templateConfig = [
            'searchFightAction' => 'Search for a fight',
        ];
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            templateConfig: $templateConfig,
        );
        $creature = $this->createMock(Creature::class);

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);
        $health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $action->expects($this->exactly(1))
            ->method('getParameter')
            ->with('level', 0)
            ->willReturn(0);

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(3))
            ->method('chance')
            ->willReturn(false);

        $creatureRepository = $this->createMock(CreatureRepository::class);
        $creatureRepository->expects($this->once())
            ->method('getRandomCreature')
            ->willReturn($creature);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("name"))
            ->willReturn("Goblin");

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("health"))
            ->willReturn(50);

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method('__call')
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn(null);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $value) {
                $this->assertSame(
                    'lotgd2.paragraph.fightTemplate.noMonstersFound',
                    $value[0]->id,
                );
                $this->assertSame(
                    'You are too blind to see any monsters. Maybe pray to the gods and ask for why that is?',
                    $value[0]->text,
                );
            });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Cannot attach attachment ' . BattleAttachment::class . ': Not installed.');

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            logger: $logger,
            attachmentRepository: $attachmentRepository,
            diceBag: $diceBag,
            creatureRepository: $creatureRepository,
            stats: $stats,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->searchAction();
    }

    public function testAddDefaultActionsWithCheats(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks();

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        // Expect both regular actions and cheat actions to be added
        $stage->expects($this->exactly(2))
            ->method('addActionGroup');

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->addDefaultActions($stage, $scene, $character);
    }

    public function testAddDefaultActionsLevelOne(): void
    {
        $templateConfig = [
            'searchFightAction' => 'Search for a fight',
            'searchThrillseekingAction' => 'Go thrillseeking',
        ];
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 1,
            templateConfig: $templateConfig,
        );

        $health = $this->createMock(HealthHandler::class);
        $health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                return count($actionGroup->getActions()) === 2; // Only search and thrillseeking, no slumming
            }));

        $fightTemplate = $this->getPartialTemplate(
            security: $security,
            health: $health,
        );

        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);
        $fightTemplate->addDefaultActions($stage, $scene, $character);
    }

    public function testGetRandomLevelChangeReturnsValidRange(): void
    {
        // Test multiple iterations to ensure we get expected range
        $results = [];

        for ($i = 0; $i < 100; $i++) {
            $diceBag = $this->createMock(DiceBagInterface::class);
            $diceBag->expects($this->exactly(2))
                ->method('chance')
                ->willReturnOnConsecutiveCalls(false, false); // No level change

            $fightTemplate = $this->getPartialTemplate(
                diceBag: $diceBag,
            );

            $result = $fightTemplate->getRandomLevelChange();
            $this->assertIsInt($result);
            $this->assertGreaterThanOrEqual(-1, $result);
            $this->assertLessThanOrEqual(1, $result);

            $results[] = $result;
        }
    }

    public function testGetRandomLevelChangePositive(): void
    {
        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(true, false); // Positive increment, no negative

        $fightTemplate = $this->getPartialTemplate(
            diceBag: $diceBag,
        );

        $result = $fightTemplate->getRandomLevelChange();
        $this->assertEquals(1, $result);
    }

    public function testGetRandomLevelChangeNegative(): void
    {
        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, true); // No positive, negative increment

        $fightTemplate = $this->getPartialTemplate(
            diceBag: $diceBag,
        );

        $result = $fightTemplate->getRandomLevelChange();
        $this->assertEquals(-1, $result);
    }

    public function testGetRandomLevelChangeBoth(): void
    {
        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(true, true); // Both increments cancel out

        $fightTemplate = $this->getPartialTemplate(
            diceBag: $diceBag,
        );

        $result = $fightTemplate->getRandomLevelChange();
        $this->assertEquals(0, $result);
    }

    public function testHandleCheatsExperience(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            templateConfig: false,
        );

        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->once())
            ->method('addExperience')
            ->with($character, 1000);

        $fightTemplate = $this->getPartialTemplate(
            stats: $stats,
        );
        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);

        $fightTemplate->handleCheats('experience');
    }

    public function testHandleCheatsGold(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            templateConfig: false,
        );

        $gold = $this->createMock(WealthHandler::class);
        $gold->expects($this->once())
            ->method('addGold')
            ->with($character, 1000);

        $fightTemplate = $this->getPartialTemplate(
            gold: $gold,
        );
        $fightTemplate->setSceneChangeParameter($stage, $action, $scene);

        $fightTemplate->handleCheats('gold');
    }

    public function testHandleCheatsInvalidCheat(): void
    {
        $stats = $this->createMock(StatsHandler::class);
        $stats->expects($this->never())
            ->method('addExperience');

        $gold = $this->createMock(WealthHandler::class);
        $gold->expects($this->never())
            ->method('addGold');

        $fightTemplate = $this->getPartialTemplate(
            stats: $stats,
            gold: $gold,
        );

        $fightTemplate->handleCheats('invalid');
    }

    public function testActionGroupSearchConstant(): void
    {
        $this->assertEquals("lotgd2.actionGroup.fightTemplate.search", FightTemplate::ActionGroupSearch);
    }

    public function testGetStage(): void
    {
        $stage = $this->createStub(Stage::class);

        $fightTemplate = $this->getMockBuilder(FightTemplate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $fightTemplate->expects($this->once())
            ->method(PropertyHook::get("stage"))
            ->willReturn($stage);

        $this->assertSame($stage, $fightTemplate->getStage());
    }

    public function testGetScene(): void
    {
        $scene = $this->createStub(Scene::class);

        $fightTemplate = $this->getMockBuilder(FightTemplate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $fightTemplate->expects($this->once())
            ->method(PropertyHook::get("scene"))
            ->willReturn($scene);

        $this->assertSame($scene, $fightTemplate->getScene());
    }
}
