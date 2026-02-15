<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\CreatureRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(FightTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(DiceBag::class)]
class FightTemplateTest extends TestCase
{
    private FightTemplate $fightTemplate;
    private Security&MockObject $security;
    private LoggerInterface&MockObject $logger;
    private AttachmentRepository&MockObject $attachmentRepository;
    private Stats&MockObject $experience;
    private Stats&MockObject $stats;
    private DiceBagInterface&MockObject $diceBag;
    private CreatureRepository&MockObject $creatureRepository;
    private Battle&MockObject $battle;
    private Health&MockObject $health;
    private Gold&MockObject $gold;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepository::class);
        $this->experience = $this->createMock(Stats::class);
        $this->stats = $this->createMock(Stats::class);
        $this->diceBag = $this->createMock(DiceBagInterface::class);
        $this->creatureRepository = $this->createMock(CreatureRepository::class);
        $this->battle = $this->createMock(Battle::class);
        $this->health = $this->createMock(Health::class);
        $this->gold = $this->createMock(Gold::class);
        $this->character = $this->createMock(Character::class);

        $this->fightTemplate = new FightTemplate(
            $this->security,
            $this->logger,
            $this->attachmentRepository,
            $this->experience,
            $this->diceBag,
            $this->creatureRepository,
            $this->battle,
            $this->health,
            $this->stats,
            $this->gold
        );
    }

    public function testOnSceneChangeWithDefaultAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $action->expects($this->once())
            ->method('getParameter')
            ->with('op')
            ->willReturn('unknown');

        $this->logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    'Called FightTemplate::onSceneChange, op=unknown' => true,
                    'Called FightTemplate::defaultAction' => true,
                    default => throw new \Exception(),
                };
            })
        ;

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight',
                'searchSlummingAction' => 'Go slumming',
                'searchThrillseekingAction' => 'Go thrillseeking'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn('Forest');

        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->fightTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithSearchAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $creature = $this->createMock(Creature::class);
        $attachment = $this->createMock(Attachment::class);
        $battleState = $this->createMock(BattleState::class);

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'search'],
                ['level', 0, 2],
                ['level', 0, 2]
            ]);

        $this->logger->expects($this->exactly(4))
            ->method('debug');

        $this->health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $this->health->expects($this->once())
            ->method('decrementTurns')
            ->willReturn($this->health);

        $this->experience->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $this->diceBag->expects($this->exactly(3))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, false, false); // No random level change, no surprise attack

        $this->creatureRepository->expects($this->once())
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

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("weapon"))
            ->willReturn("Rusty Sword");

        $this->attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn($attachment);

        $this->battle->expects($this->once())
            ->method('start')
            ->with($creature)
            ->willReturn($battleState);

        $stage->expects($this->once())
            ->method('addAttachment')
            ->with($attachment);

        $stage->expects($this->once())
            ->method('clearActionGroups');

        $this->battle->expects($this->once())
            ->method('addFightActions')
            ->with($stage, $scene, $battleState, ['op' => 'fight', 'surprise' => true]);

        $stage->expects($this->exactly(2))
            ->method('addContext')
            ->willReturnMap([
                ['creatureName', 'Goblin', $stage],
                ['creatureWeapon', 'Rusty Sword', $stage],
            ]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"));

        $this->fightTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithSearchActionWithCharacterGettingSurprised(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $creature = $this->createMock(Creature::class);
        $attachment = $this->createMock(Attachment::class);
        $battleState = $this->createMock(BattleState::class);

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'search'],
                ['level', 0, 2],
                ['level', 0, 2]
            ]);

        $this->logger->expects($this->exactly(4))
            ->method('debug');

        $this->health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $this->experience->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $this->diceBag->expects($this->exactly(3))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, false, true); // No random level change, no surprise attack

        $this->creatureRepository->expects($this->once())
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

        $creature->expects($this->atLeastOnce())
            ->method(PropertyHook::get("weapon"))
            ->willReturn("Rusty Sword");

        $this->attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn($attachment);

        $this->battle->expects($this->once())
            ->method('start')
            ->with($creature)
            ->willReturn($battleState);

        $stage->expects($this->once())
            ->method('addAttachment')
            ->with($attachment);

        $stage->expects($this->once())
            ->method('clearActionGroups');

        $this->battle->expects($this->once())
            ->method('addFightActions')
            ->with($stage, $scene, $battleState, ['op' => 'fight']);

        $stage->expects($this->exactly(2))
            ->method('addContext')
            ->willReturnMap([
                ['creatureName', 'Goblin', $stage],
                ['creatureWeapon', 'Rusty Sword', $stage],
            ]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"));

        $this->fightTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithFightAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $action->expects($this->atLeastOnce())
            ->method('getParameter')
            ->willReturnMap([
                ["op", "fight"],
                ["how", null],
            ]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called FightTemplate::onSceneChange, op=fight');

        // Mock the fightAction method by expecting it to be called
        // Since fightAction is likely implemented in DefaultFightTrait, 
        // we just verify the method dispatch works correctly
        $this->fightTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithCheats(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', null, 'cheat'],
                ['what', null, 'experience']
            ]);

        $this->security->expects($this->atLeastOnce())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        $this->stats->expects($this->once())
            ->method('addExperience')
            ->with(1000);

        $this->logger->expects($this->atLeastOnce())
            ->method('debug')
            ->withAnyParameters();

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight',
                'searchSlummingAction' => 'Go slumming',
                'searchThrillseekingAction' => 'Go thrillseeking'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn('Forest');

        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(1); // Level 1 - no slumming option

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $stage->expects($this->exactly(2))
            ->method('addActionGroup');

        $this->fightTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testDefaultActionWhenDead(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called FightTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(0);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method('addDescription')
            ->with("You are dead. You can't fight any more battles today.");

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->fightTemplate->defaultAction($stage, $action, $scene);
    }

    public function testSearchActionWhenTired(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->health->expects($this->once())
            ->method('getTurns')
            ->willReturn(0);

        $this->health->expects($this->never())
            ->method('decrementTurns')
            ->willReturn($this->health);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called FightTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn('Forest');

        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(1); // Level 1 - no slumming option

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with("You are too tired to search the forest any longer today. Perhaps tomorrow you will have more energy.");

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->fightTemplate->searchAction($stage, $action, $scene);
    }

    public function testSearchActionNoCreatureFound(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $action->expects($this->exactly(1))
            ->method('getParameter')
            ->with('level', 0)
            ->willReturn(0);

        $this->logger->expects($this->exactly(2))
            ->method('debug');

        $this->experience->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $this->diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturn(false);

        $this->creatureRepository->expects($this->once())
            ->method('getRandomCreature')
            ->willReturn(null);

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('This place looks very peaceful.');

        $this->character
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(123);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Character 123 did not find any creatures');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn('Forest');

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->fightTemplate->searchAction($stage, $action, $scene);
    }

    public function testSearchActionNoAttachment(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $creature = $this->createMock(Creature::class);

        $this->health->expects($this->once())
            ->method('getTurns')
            ->willReturn(10);

        $action->expects($this->exactly(1))
            ->method('getParameter')
            ->with('level', 0)
            ->willReturn(0);

        $this->experience->expects($this->once())
            ->method('getLevel')
            ->willReturn(5);

        $this->diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturn(false);

        $this->creatureRepository->expects($this->once())
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

        $this->attachmentRepository->expects($this->once())
            ->method('__call')
            ->with("findOneByAttachmentClass", [BattleAttachment::class])
            ->willReturn(null);

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('You are too blind to see any monsters. Maybe prey to the gods and ask for why that is?');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Cannot attach attachment ' . BattleAttachment::class . ': Not installed.');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn('Forest');

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->fightTemplate->searchAction($stage, $action, $scene);
    }

    public function testAddDefaultActionsWithCheats(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight',
                'searchSlummingAction' => 'Go slumming',
                'searchThrillseekingAction' => 'Go thrillseeking'
            ]);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn("Forest");

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        // Expect both regular actions and cheat actions to be added
        $stage->expects($this->exactly(2))
            ->method('addActionGroup');

        $this->fightTemplate->addDefaultActions($stage, $action, $scene);
    }

    public function testAddDefaultActionsLevelOne(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn([
                'searchFightAction' => 'Search for a fight',
                'searchThrillseekingAction' => 'Go thrillseeking'
            ])
        ;

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("title"))
            ->willReturn("Forest")
        ;

        $this->character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(1); // Level 1 - no slumming option

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($this->character);

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                return count($actionGroup->getActions()) === 2; // Only search and thrillseeking, no slumming
            }));

        $this->fightTemplate->addDefaultActions($stage, $action, $scene);
    }

    public function testGetRandomLevelChangeReturnsValidRange(): void
    {
        // Test multiple iterations to ensure we get expected range
        $results = [];
        
        for ($i = 0; $i < 100; $i++) {
            // Mock dice bag to return predictable results
            $this->diceBag->expects($this->exactly(2))
                ->method('chance')
                ->willReturnOnConsecutiveCalls(false, false); // No level change
            
            $result = $this->fightTemplate->getRandomLevelChange();
            $this->assertIsInt($result);
            $this->assertGreaterThanOrEqual(-1, $result);
            $this->assertLessThanOrEqual(1, $result);
            
            $results[] = $result;
            
            // Reset the mock for next iteration
            $this->setUp();
        }
    }

    public function testGetRandomLevelChangePositive(): void
    {
        $this->diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(true, false); // Positive increment, no negative

        $result = $this->fightTemplate->getRandomLevelChange();
        $this->assertEquals(1, $result);
    }

    public function testGetRandomLevelChangeNegative(): void
    {
        $this->diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(false, true); // No positive, negative increment

        $result = $this->fightTemplate->getRandomLevelChange();
        $this->assertEquals(-1, $result);
    }

    public function testGetRandomLevelChangeBoth(): void
    {
        $this->diceBag->expects($this->exactly(2))
            ->method('chance')
            ->willReturnOnConsecutiveCalls(true, true); // Both increments cancel out

        $result = $this->fightTemplate->getRandomLevelChange();
        $this->assertEquals(0, $result);
    }

    public function testHandleCheatsExperience(): void
    {
        $this->stats->expects($this->once())
            ->method('addExperience')
            ->with(1000);

        $this->fightTemplate->handleCheats('experience');
    }

    public function testHandleCheatsGold(): void
    {
        $this->gold->expects($this->once())
            ->method('addGold')
            ->with(1000);

        $this->fightTemplate->handleCheats('gold');
    }

    public function testHandleCheatsInvalidCheat(): void
    {
        // No expectations - invalid cheat should do nothing
        $this->stats->expects($this->never())
            ->method('addExperience');

        $this->gold->expects($this->never())
            ->method('addGold');

        $this->fightTemplate->handleCheats('invalid');
    }

    public function testActionGroupSearchConstant(): void
    {
        $this->assertEquals("lotgd2.actionGroup.fightTemplate.search", FightTemplate::ActionGroupSearch);
    }
}