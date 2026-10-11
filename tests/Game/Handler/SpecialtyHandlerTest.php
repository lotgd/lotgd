<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Character\CharacterSpecialty;
use LotGD2\Entity\Character\SpecialtySkill;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Specialty;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Event\BattleSkillActivationEvent;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Handler\SpecialtyHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Repository\SpecialtyRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFunction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Stopwatch\Stopwatch;

#[CoversClass(SpecialtyHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(Specialty::class)]
#[UsesClass(CharacterSpecialty::class)]
#[UsesClass(SpecialtySkill::class)]
#[UsesClass(Stage::class)]
#[UsesClass(Scene::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(Buff::class)]
#[UsesClass(ProtoBuff::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(StageChangeEvent::class)]
#[UsesClass(BattleNavigationChangeEvent::class)]
#[UsesClass(BattleSkillActivationEvent::class)]
#[UsesClass(BuffHandler::class)]
#[UsesFunction("LotGD2\array_filter_class")]
#[AllowMockObjectsWithoutExpectations]
class SpecialtyHandlerTest extends TestCase
{
    /**
     * @param array<int|string, mixed>|null $specialties
     */
    private function createCharacter(
        ?string $name = "Hero",
        ?int $id = 123,
        ?int $mainSpecialty = null,
        ?array $specialties = null,
        ?int $level = null,
    ): Character {
        $character = new Character(name: $name);
        if ($id !== null) {
            $reflection = new ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }
        if ($mainSpecialty !== null) {
            $character->setProperty(SpecialtyHandler::MainSpecialtyProperty, $mainSpecialty);
        }
        if ($specialties !== null) {
            $character->setProperty(SpecialtyHandler::SpecialtyProperty, $specialties);
        }
        if ($level !== null) {
            $character->level = $level;
        }

        return $character;
    }

    private function createSpecialtyHandler(
        ?LoggerInterface $logger = null,
        ?Security $security = null,
        ?Stopwatch $stopwatch = null,
        ?SpecialtyRepository $specialtyRepository = null,
    ): SpecialtyHandler {
        return new SpecialtyHandler(
            $logger ?? $this->createStub(LoggerInterface::class),
            $security ?? $this->createStub(Security::class),
            $stopwatch ?? new Stopwatch(),
            $specialtyRepository ?? $this->createStub(SpecialtyRepository::class),
        );
    }

    private function setSpecialtyId(Specialty $specialty, int $id): Specialty
    {
        $reflection = new ReflectionProperty(Specialty::class, "id");
        $reflection->setValue($specialty, $id);
        return $specialty;
    }

    private function createBattleState(Character $character): BattleState
    {
        $goodGuy = $this->createStub(FighterInterface::class);
        $badGuy = $this->createStub(FighterInterface::class);
        $battleState = new BattleState($goodGuy, $badGuy);
        $battleState->setCharacter($character);
        return $battleState;
    }

    public function testGetAndHasSpecialtyWhenNotSet(): void
    {
        $character = $this->createCharacter();
        $specialty = $this->setSpecialtyId(new Specialty(name: "Dark Arts"), 1);

        $handler = $this->createSpecialtyHandler();

        $this->assertNull($handler->getSpecialty($character, null));
        $this->assertNull($handler->getSpecialty($character, $specialty));
        $this->assertSame([], $handler->getSpecialties($character));
        $this->assertNull($handler->getMainSpecialty($character));
        $this->assertNull($handler->getMainCharacterSpecialty($character));
        $this->assertFalse($handler->hasMainSpecialty($character));
        $this->assertFalse($handler->hasMainSpecialty($character, $specialty));
    }

    public function testGetMainCharacterSpecialtyWithCorruptData(): void
    {
        $character = $this->createCharacter(
            mainSpecialty: 1,
            specialties: [1 => "not_a_character_specialty_instance"],
        );

        $handler = $this->createSpecialtyHandler();
        $this->assertNull($handler->getMainCharacterSpecialty($character));

        // When getSpecialties returns an array with a non-CharacterSpecialty value
        $partialHandler = $this->getMockBuilder(SpecialtyHandler::class)
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(Security::class),
                new Stopwatch(),
                $this->createStub(SpecialtyRepository::class),
            ])
            ->onlyMethods(["getSpecialties"])
            ->getMock();

        $partialHandler->method("getSpecialties")->willReturn([
            1 => new \stdClass(),
        ]);

        $this->assertNull($partialHandler->getMainCharacterSpecialty($character));
    }

    public function testAddSpecialtyAndSetMainSpecialty(): void
    {
        $character = $this->createCharacter();
        $specialty1 = $this->setSpecialtyId(new Specialty(name: "Dark Arts"), 1);
        $specialty2 = $this->setSpecialtyId(new Specialty(name: "Mystic Magic"), 2);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")
            ->willReturnCallback(function (int $id) use ($specialty1, $specialty2) {
                return $id === 1 ? $specialty1 : ($id === 2 ? $specialty2 : null);
            });

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);

        // Setting main specialty creates character specialty if not set
        $handler->setMainSpecialty($character, $specialty1);
        $this->assertTrue($handler->hasMainSpecialty($character));
        $this->assertTrue($handler->hasMainSpecialty($character, $specialty1));
        $this->assertFalse($handler->hasMainSpecialty($character, $specialty2));

        $charSpec1 = $handler->getSpecialty($character, $specialty1);
        $this->assertNotNull($charSpec1);
        $this->assertSame(1, $charSpec1->id);

        $this->assertSame($specialty1, $handler->getMainSpecialty($character));
        $this->assertSame($charSpec1, $handler->getMainCharacterSpecialty($character));

        // Add second specialty and set it as main
        $charSpec2 = new CharacterSpecialty($specialty2);
        $charSpec2->level = 3;
        $charSpec2->uses = 1;
        $character->setProperty(SpecialtyHandler::SpecialtyProperty, [
            1 => $charSpec1,
            2 => $charSpec2,
        ]);

        $this->assertCount(2, $handler->getSpecialties($character));

        $handler->setMainSpecialty($character, $specialty2);
        $this->assertSame($specialty2, $handler->getMainSpecialty($character));
        $this->assertTrue($handler->hasMainSpecialty($character, $specialty2));

        // Setting main specialty when already present in specialties list
        $handler->setMainSpecialty($character, $specialty1);
        $this->assertSame($specialty1, $handler->getMainSpecialty($character));
    }

    public function testHasMainSpecialtyWhenIdNotFoundInDatabase(): void
    {
        $character = $this->createCharacter(mainSpecialty: 999);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")
            ->with(999)
            ->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("warning")
            ->with($this->stringContains("cannot be found in the database"));

        $handler = $this->createSpecialtyHandler(
            logger: $logger,
            specialtyRepository: $specialtyRepository,
        );
        $this->assertFalse($handler->hasMainSpecialty($character));
        $this->assertNull($handler->getMainSpecialty($character));
    }

    public function testIncrementMainSpecialty(): void
    {
        $character = $this->createCharacter();
        $specialty = $this->setSpecialtyId(new Specialty(name: "Thievery"), 1);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")->with(1)->willReturn($specialty);

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);

        // Set main specialty with initial level 1, uses 1
        $handler->setMainSpecialty($character, $specialty);
        $charSpec = $handler->getMainCharacterSpecialty($character);
        $this->assertNotNull($charSpec);
        $charSpec->level = 1;
        $charSpec->uses = 0;

        // Level goes 1 -> 2 (floor(2/3) = 0, no extra use)
        $this->assertFalse($handler->incrementMainSpecialty($character, 1));
        $this->assertSame(2, $charSpec->level);
        $this->assertSame(0, $charSpec->uses);

        // Level goes 2 -> 3 (floor(3/3) = 1, extra use gained!)
        $this->assertTrue($handler->incrementMainSpecialty($character, 1));
        $this->assertSame(3, $charSpec->level);
        $this->assertSame(1, $charSpec->uses);

        // Level goes 3 -> 6 (floor(6/3) = 2, extra use gained!)
        $this->assertTrue($handler->incrementMainSpecialty($character, 3));
        $this->assertSame(6, $charSpec->level);
        $this->assertSame(2, $charSpec->uses);
    }

    public function testOnNewDayAfterEvent(): void
    {
        $specialty1 = $this->setSpecialtyId(new Specialty(name: "Dark Arts"), 1);
        $specialty2 = $this->setSpecialtyId(new Specialty(name: "Healing"), 2);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")
            ->willReturnCallback(fn($id) => $id === 1 ? $specialty1 : ($id === 2 ? $specialty2 : null));

        $charSpec1 = new CharacterSpecialty($specialty1);
        $charSpec1->level = 6; // 6 / 3 = 2 + 1 (main) = 3 uses
        $charSpec1->uses = 0;

        $charSpec2 = new CharacterSpecialty($specialty2);
        $charSpec2->level = 4; // 4 / 3 = 1 use (side specialty)
        $charSpec2->uses = 0;

        $character = $this->createCharacter(
            mainSpecialty: 1,
            specialties: [
                1 => $charSpec1,
                2 => $charSpec2,
            ],
        );

        $stage = new Stage();
        $stage->owner = $character;
        $event = new StageChangeEvent($stage, new Action(), new Scene());

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $handler->onNewDayAfterEvent($event);

        $this->assertSame(3, $charSpec1->uses);
        $this->assertSame(1, $charSpec2->uses);

        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(1, $paragraphs);
        $this->assertSame("lotgd2.paragraph.StandardRace.onNewDay", $paragraphs[0]->id);
        $this->assertSame("Dark Arts", $paragraphs[0]->context["specialty"]);
        $this->assertSame(1, $paragraphs[0]->context["extraUsesOnMain"]);
    }

    public function testOnNewDayEventWithoutActionParameterAndNoMainSpecialty(): void
    {
        $specialty = $this->setSpecialtyId(new Specialty(name: "Dark Arts", description: "Dark desc"), 1);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->once())
            ->method("findAll")
            ->willReturn([$specialty]);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $event = new StageChangeEvent($stage, new Action(), new Scene());

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $handler->onNewDayEvent($event);

        $this->assertTrue($event->stopRender);
        $this->assertSame("Childhood memories", $stage->title);
    }

    public function testOnNewDayEventWhenAlreadyHasMainSpecialty(): void
    {
        $specialty = $this->setSpecialtyId(new Specialty(name: "Dark Arts", description: "Dark desc"), 1);
        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")->with(1)->willReturn($specialty);

        $character = $this->createCharacter(mainSpecialty: 1);

        $stage = new Stage();
        $stage->owner = $character;
        $event = new StageChangeEvent($stage, new Action(), new Scene());

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $handler->onNewDayEvent($event);

        $this->assertFalse($event->stopRender);
        $this->assertNull($stage->title);
    }

    public function testOnNewDayEventWithSpecialtyActionParameterAndSelectionSuccess(): void
    {
        $specialty = $this->setSpecialtyId(new Specialty(
            name: "Mystic Magic",
            description: "Magic desc",
            selectionText: "You chose magic"
        ), 2);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->atLeastOnce())
            ->method("find")
            ->with(2)
            ->willReturn($specialty);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->addActionGroup(new ActionGroup(ActionGroup::EMPTY, ""));
        $stage->owner = $character;
        $action = new Action(parameters: ["specialty" => 2]);
        $event = new StageChangeEvent($stage, $action, new Scene());

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $handler->onNewDayEvent($event);

        $this->assertTrue($event->stopRender);
        $this->assertSame("Childhood memories", $stage->title);
        $this->assertSame("You chose magic", array_values($stage->paragraphs)[0]->text);
        $this->assertSame($specialty, $handler->getMainSpecialty($character));
    }

    public function testDoSpecialtySelectionWhenSpecialtyDisappeared(): void
    {
        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->once())
            ->method("find")
            ->with(99)
            ->willReturn(null);

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $action = new Action(parameters: ["specialty" => 99]);
        $event = new StageChangeEvent($stage, $action, new Scene());

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $result = $handler->doSpecialtySelection($event);

        $this->assertFalse($result);
    }

    public function testShowSpecialtySelectionWhenNoSpecialtiesAvailable(): void
    {
        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->once())
            ->method("findAll")
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("info")
            ->with("There are no specialties available. Skipping specialty selection.");

        $character = $this->createCharacter();
        $stage = new Stage();
        $stage->owner = $character;
        $event = new StageChangeEvent($stage, new Action(), new Scene());

        $handler = $this->createSpecialtyHandler(
            logger: $logger,
            specialtyRepository: $specialtyRepository,
        );
        $result = $handler->showSpecialtySelection($event);

        $this->assertFalse($result);
    }

    public function testOnBattleNavigationAddSkills(): void
    {
        $protoBuff = new ProtoBuff(
            id: "pb1",
            name: "Magic Shield",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 1,
        );
        $skill1 = new SpecialtySkill("Cheap Spell", 1, $protoBuff);
        $skill2 = new SpecialtySkill("Expensive Spell", 5, $protoBuff);

        $specialty = $this->setSpecialtyId(new Specialty(
            name: "Mystic",
            skills: ["cheap" => $skill1, "expensive" => $skill2]
        ), 1);

        $specialtyEmpty = $this->setSpecialtyId(new Specialty(name: "Empty", skills: []), 4);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")
            ->willReturnCallback(fn($id) => match($id) {
                1 => $specialty,
                4 => $specialtyEmpty,
                default => null,
            });

        $charSpec = new CharacterSpecialty($specialty);
        $charSpec->uses = 2; // can afford skill1 (costs 1) but not skill2 (costs 5)
        $character = $this->createCharacter(specialties: [1 => $charSpec]);

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);

        $actionGroup = new ActionGroup("fight", "Fight");
        $battleState = $this->createBattleState($character);
        $scene = new Scene();
        $event = new BattleNavigationChangeEvent($character, $battleState, [$actionGroup], $scene, []);

        $handler->onBattleNavigationAddSkills($event);

        $this->assertCount(2, $event->actionGroups);
        $specialtyGroup = $event->actionGroups[1];
        $this->assertSame("lotgd2.actionGroup.Specialty.1", $specialtyGroup->id);
        $this->assertCount(1, $specialtyGroup->actions);
        $this->assertSame("Cheap Spell (Costs: 1)", array_values($specialtyGroup->actions)[0]->title);

        // When uses <= 0, no group added
        $charSpec->uses = 0;
        $eventNoUses = new BattleNavigationChangeEvent($character, $battleState, [$actionGroup], $scene, []);
        $handler->onBattleNavigationAddSkills($eventNoUses);
        $this->assertCount(1, $eventNoUses->actionGroups);

        // When specialty has no skills, addedSkillCount is 0 and action group is not added
        $specialtyEmpty = $this->setSpecialtyId(new Specialty(name: "Empty", skills: []), 4);
        $charSpec4 = new CharacterSpecialty($specialtyEmpty);
        $charSpec4->id = 4;
        $charSpec4->uses = 2;

        $characterNoSkills = $this->createCharacter(specialties: [4 => $charSpec4]);

        $eventSkip = new BattleNavigationChangeEvent($characterNoSkills, $battleState, [$actionGroup], $scene, []);
        $handler->onBattleNavigationAddSkills($eventSkip);
        $this->assertCount(1, $eventSkip->actionGroups);
    }

    public function testOnBattleNavigationAddCheats(): void
    {
        $character = $this->createCharacter();
        $battleState = $this->createBattleState($character);
        $scene = new Scene();

        // When cheats disabled
        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(false);

        $handler = $this->createSpecialtyHandler(security: $security);

        $event = new BattleNavigationChangeEvent($character, $battleState, [], $scene, []);
        $handler->onBattleNavigationAddCheats($event);
        $this->assertCount(0, $event->actionGroups);

        // When cheats enabled
        $securityCheats = $this->createMock(Security::class);
        $securityCheats->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(true);

        $handlerCheats = $this->createSpecialtyHandler(security: $securityCheats);
        $eventCheats = new BattleNavigationChangeEvent($character, $battleState, [], $scene, []);
        $handlerCheats->onBattleNavigationAddCheats($eventCheats);
        $this->assertCount(1, $eventCheats->actionGroups);
        $this->assertSame("Cheats", $eventCheats->actionGroups[0]->title);
    }

    public function testOnSkillActivationEventWhenCheatsDisabledOrSkillMismatch(): void
    {
        $character = $this->createCharacter();
        $action = new Action();
        $buffHandler = $this->createMock(BuffHandler::class);
        $event = new BattleSkillActivationEvent($character, $buffHandler, "specialty", $action);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(false);

        $handler = $this->createSpecialtyHandler(security: $security);
        $handler->onSkillActivationEvent($event);
        $this->assertFalse($event->isPropagationStopped());

        // When skillName !== specialty
        $securityMismatch = $this->createMock(Security::class);
        $securityMismatch->method("isGranted")->willReturn(true);

        $handler2 = $this->createSpecialtyHandler(security: $securityMismatch);
        $eventMismatch = new BattleSkillActivationEvent($character, $buffHandler, "other_skill", $action);
        $handler2->onSkillActivationEvent($eventMismatch);
        $this->assertFalse($eventMismatch->isPropagationStopped());
    }

    public function testOnSkillActivationEventSkillNotFoundOrInsufficientUses(): void
    {
        $security = $this->createMock(Security::class);
        $security->method("isGranted")->willReturn(true);

        $specialty = $this->setSpecialtyId(new Specialty(
            name: "Sorcery",
            skills: []
        ), 1);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->once())
            ->method("find")
            ->with(1)
            ->willReturn($specialty);

        $character = $this->createCharacter();
        $action = new Action(parameters: [
            "specialty" => 1,
            "specialty_skill" => "cheap",
        ]);

        $buffHandler = $this->createMock(BuffHandler::class);
        $buffHandler->expects($this->once())
            ->method("addBuff")
            ->with($character, $this->callback(function (Buff $buff) {
                return $buff->id === "lotgd2.buff.DefaultFightTrait.SkillNotFound";
            }));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("warning")
            ->with($this->stringContains("Skill transfer was errornous"));

        $event = new BattleSkillActivationEvent($character, $buffHandler, "specialty", $action);

        $handler = $this->createSpecialtyHandler(
            logger: $logger,
            security: $security,
            specialtyRepository: $specialtyRepository,
        );
        $handler->onSkillActivationEvent($event);

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnSkillActivationEventSuccess(): void
    {
        $security = $this->createMock(Security::class);
        $security->method("isGranted")->willReturn(true);

        $protoBuff = new ProtoBuff(
            id: "pb1",
            name: "Fireball Buff",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 1,
        );
        $skill = new SpecialtySkill("Fireball", 2, $protoBuff);
        $specialty = $this->setSpecialtyId(new Specialty(
            name: "Sorcery",
            skills: ["fireball" => $skill]
        ), 1);

        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->expects($this->once())
            ->method("find")
            ->with(1)
            ->willReturn($specialty);

        $charSpec = new CharacterSpecialty($specialty);
        $charSpec->uses = 3;
        $character = $this->createCharacter(specialties: [1 => $charSpec]);

        $action = new Action(parameters: [
            "specialty" => 1,
            "specialty_skill" => "fireball",
        ]);

        $buffHandler = $this->createMock(BuffHandler::class);
        $buffHandler->expects($this->once())
            ->method("addBuffFromPrototype")
            ->with($character, $protoBuff);

        $event = new BattleSkillActivationEvent($character, $buffHandler, "specialty", $action);

        $handler = $this->createSpecialtyHandler(
            security: $security,
            specialtyRepository: $specialtyRepository,
        );
        $handler->onSkillActivationEvent($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame(1, $charSpec->uses); // 3 - 2 = 1
    }

    public function testOnCheatSkillActivationEvent(): void
    {
        $character = $this->createCharacter();
        $action = new Action();
        $buffHandler = $this->createMock(BuffHandler::class);

        // When cheats disabled
        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(false);

        $handler = $this->createSpecialtyHandler(security: $security);
        $event = new BattleSkillActivationEvent($character, $buffHandler, "godmode", $action);
        $handler->onCheatSkillActivationEvent($event);
        $this->assertFalse($event->isPropagationStopped());

        // When cheats enabled and skillName === godmode
        $securityCheats = $this->createMock(Security::class);
        $securityCheats->method("isGranted")->willReturn(true);

        $buffHandler->expects($this->once())
            ->method("addBuff")
            ->with($character, $this->callback(function (Buff $buff) {
                return $buff->id === "lotgd2.buff.DefaultFightTrait.GodMode"
                    && $buff->goodGuyInvulnerable === true;
            }));

        $handlerCheats = $this->createSpecialtyHandler(security: $securityCheats);
        $eventGod = new BattleSkillActivationEvent($character, $buffHandler, "godmode", $action);
        $handlerCheats->onCheatSkillActivationEvent($eventGod);
        $this->assertTrue($eventGod->isPropagationStopped());

        // Skill mismatch
        $eventMismatch = new BattleSkillActivationEvent($character, $buffHandler, "other", $action);
        $handlerCheats->onCheatSkillActivationEvent($eventMismatch);
        $this->assertFalse($eventMismatch->isPropagationStopped());
    }

    public function testOnCharacterLevelIncrease(): void
    {
        $specialty = $this->setSpecialtyId(new Specialty(name: "Stealth"), 1);
        $specialtyRepository = $this->createMock(SpecialtyRepository::class);
        $specialtyRepository->method("find")->with(1)->willReturn($specialty);

        $charSpec = new CharacterSpecialty($specialty);
        $charSpec->level = 1;
        $charSpec->uses = 0;

        $character = $this->createCharacter(
            level: 3,
            mainSpecialty: 1,
            specialties: [1 => $charSpec],
        );

        $characterBefore = $this->createCharacter(level: 1);

        $stage = new Stage();
        $event = new CharacterChangeEvent($character, $characterBefore, $stage);

        $handler = $this->createSpecialtyHandler(specialtyRepository: $specialtyRepository);
        $handler->onCharacterLevelIncrease($event);

        $this->assertSame(3, $charSpec->level); // 1 + 2 = 3
        $this->assertSame(1, $charSpec->uses); // gained extra use

        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(1, $paragraphs);
        $this->assertSame("lotgd2.paragraph.SpecialtyHandler.specialtyIncrement", $paragraphs[0]->id);
        $this->assertTrue($paragraphs[0]->context["hasMoreUses"]);
        $this->assertSame(2, $paragraphs[0]->context["level"]);

        // When delta level > 0 but hasMoreUses is false
        $charSpecNoExtra = new CharacterSpecialty($specialty);
        $charSpecNoExtra->level = 0;
        $charSpecNoExtra->uses = 0;

        $character3 = $this->createCharacter(
            level: 2,
            mainSpecialty: 1,
            specialties: [1 => $charSpecNoExtra],
        );

        $characterBefore3 = $this->createCharacter(level: 1);

        $stage3 = new Stage();
        $event3 = new CharacterChangeEvent($character3, $characterBefore3, $stage3);
        $handler->onCharacterLevelIncrease($event3);

        $paragraphs3 = array_values($stage3->paragraphs);
        $this->assertCount(1, $paragraphs3);
        $this->assertFalse($paragraphs3[0]->context["hasMoreUses"]);
        $this->assertSame(1, $paragraphs3[0]->context["level"]);
        $this->assertSame(2, $paragraphs3[0]->context["levelsUntilNext"]);

        // When delta level <= 0
        $characterBefore2 = $this->createCharacter(level: 3);
        $stage2 = new Stage();
        $event2 = new CharacterChangeEvent($character, $characterBefore2, $stage2);
        $handler->onCharacterLevelIncrease($event2);
        $this->assertCount(0, $stage2->paragraphs);
    }

    public function testOnCharacterReset(): void
    {
        $character = $this->createCharacter(
            mainSpecialty: 1,
            specialties: ["some_data"],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with($this->stringContains("Resetting specialties"));

        $handler = $this->createSpecialtyHandler(logger: $logger);

        $event = new CharacterChangeEvent($character, $character);
        $handler->onCharacterReset($event);

        $this->assertNull($character->getProperty(SpecialtyHandler::MainSpecialtyProperty));
        $this->assertSame([], $character->getProperty(SpecialtyHandler::SpecialtyProperty));
    }

    public function testDiceBagProperty(): void
    {
        $handler = $this->createSpecialtyHandler();
        $this->assertInstanceOf(DiceBag::class, $handler->diceBag);

        $customDiceBag = new DiceBag();
        $handler->diceBag = $customDiceBag;
        $this->assertSame($customDiceBag, $handler->diceBag);
    }
}
