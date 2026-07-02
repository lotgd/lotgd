<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Master;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Scene\SceneTemplate\TrainingTemplate;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\MasterRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(TrainingTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(SimpleStageParameterEvent::class)]
class TrainingTemplateTest extends TestCase
{
    public function testGetContext(): void
    {
        $statsHandler = $this->createMock(StatsHandler::class);
        $statsHandler->expects($this->once())->method("getExperience")->willReturn(50);
        $statsHandler->expects($this->once())->method("getRequiredExperience")->willReturn(2500);

        $equipmentHandler = $this->createMock(EquipmentHandler::class);
        $equipmentHandler->expects($this->exactly(2))->method("getItemInSlot")
            ->willReturn(null);

        $trainingTemplate = $this->getStubBuilder(TrainingTemplate::class)
            ->setConstructorArgs([
                $this->createStub(Security::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(MasterRepository::class),
                $this->createStub(Battle::class),
                $equipmentHandler,
                $statsHandler,
                $this->createStub(HealthHandler::class),
                $this->createStub(GoldHandler::class),
            ])
            ->onlyMethods([])
            ->getStub();

        $this->assertSame([
            "experience" => 50,
            "requiredExperience" => 2500,
            "weapon" => "Fists",
            "armor" => "T-Shirt",
        ], $trainingTemplate->getContext());
    }

    #[TestWith([[], "defaultAction"])]
    #[TestWith([["op" => ""], "defaultAction"])]
    #[TestWith([["op" => "ask"], "askAction"])]
    #[TestWith([["op" => "challenge"], "challengeAction"])]
    #[TestWith([["op" => "cheat"], "defaultAction"])]
    public function testOnSceneChangeCallsExpectedSubMethodsAndChecksOpActionParameter(
        ?array $actionParameters,
        string $calledMethod,
    ): void {
        $security = $this->createMock(Security::class);
        $security->method("isGranted")->with("ROLE_CHEATS_ENABLED")->willReturn(false);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->setConstructorArgs([
                $security,
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(MasterRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(EquipmentHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(HealthHandler::class),
                $this->createStub(GoldHandler::class),
            ])
            ->onlyMethods(["defaultAction", "askAction", "challengeAction", "handleCheats"])
            ->getMock();

        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameters")->willReturn($actionParameters);

        $trainingTemplate->expects($this->atLeastOnce())->method(PropertyHook::get("action"))->willReturn($action);
        $trainingTemplate->expects($this->once())->method($calledMethod);

        $trainingTemplate->expects($this->never())->method("handleCheats");

        $trainingTemplate->onSceneChange();
    }

    #[TestWith(["what"])]
    #[TestWith(["gold"])]
    public function testOnSceneChangeCallsCheatHandlerIfItsGranted(
        ?string $cheatParameter,
    ): void {
        $security = $this->createMock(Security::class);
        $security->method("isGranted")->with("ROLE_CHEATS_ENABLED")->willReturn(true);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->setConstructorArgs([
                $security,
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(MasterRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(EquipmentHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(HealthHandler::class),
                $this->createStub(GoldHandler::class),
            ])
            ->onlyMethods(["defaultAction", "askAction", "challengeAction", "handleCheats"])
            ->getMock();

        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameters")->willReturn([
            "op" => "cheat",
            "what" => $cheatParameter,
        ]);

        $action->expects($this->once())->method("getParameter")->with("what")->willReturn($cheatParameter);

        $character = $this->createStub(Character::class);
        $stage = $this->createStub(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $trainingTemplate->expects($this->atLeastOnce())->method(PropertyHook::get("action"))->willReturn($action);
        $trainingTemplate->expects($this->once())->method(PropertyHook::get("stage"))->willReturn($stage);

        $trainingTemplate->expects($this->once())->method("handleCheats")->with($character, $cheatParameter);
        $trainingTemplate->expects($this->once())->method("defaultAction");

        $trainingTemplate->onSceneChange();
    }

    public function testDefaultActionRefusesInteractionWhenCharacterIsDead()
    {
        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("isAlive")->willReturn(false);

        $masterRepository = $this->createMock(MasterRepository::class);
        $masterRepository->expects($this->never())->method("getByLevel");

        $trainingTemplate = new TrainingTemplate(
            $this->createStub(Security::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(AttachmentRepository::class),
            $masterRepository,
            $this->createStub(Battle::class),
            $this->createStub(EquipmentHandler::class),
            $this->createStub(StatsHandler::class),
            $healthHandler,
            $this->createStub(GoldHandler::class),
        );

        $character = $this->createStub(Character::class);
        $stage = $this->createMock(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.isDead", $paragraphs[0]->id);
        });
        $stage->expects($this->never())->method("addActionGroup");
        $stage->expects($this->never())->method("addAction");

        $scene = $this->createStub(Scene::class);
        $action = $this->createStub(Action::class);

        $trainingTemplate->setSceneChangeParameter($stage, $action, $scene);
        $trainingTemplate->defaultAction();
    }

    #[TestWith([1])]
    #[TestWith([14])]
    #[TestWith([15])]
    #[TestWith([255])]
    public function testDefaultActionRefusesInteractionWhenNoMasterWasFound(int $level)
    {

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("isAlive")->willReturn(true);

        $masterRepository = $this->createMock(MasterRepository::class);
        $masterRepository->expects($this->once())->method("getByLevel")->with($level)->willReturn(null);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->onlyMethods(["getContext"])
            ->setConstructorArgs([
                $this->createStub(Security::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $masterRepository,
                $this->createStub(Battle::class),
                $this->createStub(EquipmentHandler::class),
                $this->createStub(StatsHandler::class),
                $healthHandler,
                $this->createStub(GoldHandler::class),
            ])
            ->getMock();

        $trainingTemplate->expects($this->once())->method("getContext")->willReturn([]);

        $character = $this->createStub(Character::class);
        $character->method(PropertyHook::get("level"))->willReturn($level);
        $stage = $this->createMock(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.maxLevelReached", $paragraphs[0]->id);
            $this->assertSame("You have reached the max level", $paragraphs[0]->text);
            $this->assertSame("Miyaura", $paragraphs[0]->context["campLeader"]);
        });

        $stage->expects($this->never())->method("addActionGroup");
        $stage->expects($this->never())->method("addAction");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "maxLevelReached" => "You have reached the max level",
            ],
            "campLeader" => "Miyaura",
        ]);

        $action = $this->createStub(Action::class);

        $trainingTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        $trainingTemplate->method(PropertyHook::get("action"))->willReturn($action);
        $trainingTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        $trainingTemplate->method(PropertyHook::get("character"))->willReturn($character);

        $trainingTemplate->defaultAction();
    }

    #[TestWith([1, ["hello" => "world"]])]
    #[TestWith([14, ["hello" => "world", "weapon" => "Sodium stick"]])]
    #[TestWith([15, []])]
    #[TestWith([255, ["armor" => "Mask", "weapon" => "LiAlH4 dust"]])]
    public function testDefaultActionAddsDefaultActionsAndAddsContextWhenMasterWasFound(int $level, array $context)
    {
        $master = $this->createStub(Master::class);
        $master->method(PropertyHook::get("name"))->willReturn("The Master");

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("isAlive")->willReturn(true);

        $masterRepository = $this->createMock(MasterRepository::class);
        $masterRepository->expects($this->once())->method("getByLevel")->with($level)->willReturn($master);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->onlyMethods(["getContext", "addDefaultActions"])
            ->setConstructorArgs([
                $this->createStub(Security::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $masterRepository,
                $this->createStub(Battle::class),
                $this->createStub(EquipmentHandler::class),
                $this->createStub(StatsHandler::class),
                $healthHandler,
                $this->createStub(GoldHandler::class),
            ])
            ->getMock();

        $trainingTemplate->expects($this->once())->method("getContext")->willReturn($context);
        $trainingTemplate->expects($this->once())->method("addDefaultActions");

        $paragraph = $this->createMock(Paragraph::class);
        $calls = 0;
        $paragraph->expects($this->exactly(1 + count($context)))->method('addContext')->willReturnCallback(
            function (string $parameter, mixed $contextVariable) use (&$calls, $context, $master, $paragraph) {
                if ($calls === 0) {
                    $this->assertSame("The Master", $master->name);
                } else {
                    $this->assertSame(array_keys($context)[$calls - 1], $parameter);
                    $this->assertSame($context[$parameter], $contextVariable);
                }

                $calls++;

                return $paragraph;
            }
        );

        $character = $this->createStub(Character::class);
        $character->method(PropertyHook::get("level"))->willReturn($level);
        $stage = $this->createMock(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $stage->expects($this->never())->method(PropertyHook::set("paragraphs"));
        $stage->expects($this->once())->method(PropertyHook::get("paragraphs"))->willReturn([
            Stage::SceneText => $paragraph,
        ]);

        $stage->expects($this->never())->method("addActionGroup");
        $stage->expects($this->never())->method("addAction");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "maxLevelReached" => "You have reached the max level",
            ],
            "campLeader" => "Miyaura",
        ]);

        $action = $this->createStub(Action::class);

        $trainingTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        $trainingTemplate->method(PropertyHook::get("action"))->willReturn($action);
        $trainingTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        $trainingTemplate->method(PropertyHook::get("character"))->willReturn($character);

        $trainingTemplate->defaultAction();
    }

    public function testIfAsksMasterCallsDefaultActionsAndShowsTheConfiguredReply()
    {
        $master = $this->createStub(Master::class);
        $master->method(PropertyHook::get("name"))->willReturn("The Master");

        $masterRepository = $this->createMock(MasterRepository::class);
        $masterRepository->expects($this->once())->method("getByLevel")->willReturn($master);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->onlyMethods(["getContext", "addDefaultActions"])
            ->setConstructorArgs([
                $this->createStub(Security::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $masterRepository,
                $this->createStub(Battle::class),
                $this->createStub(EquipmentHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(HealthHandler::class),
                $this->createStub(GoldHandler::class),
            ])
            ->getMock();

        $trainingTemplate->expects($this->once())->method("getContext")->willReturn([]);
        $trainingTemplate->expects($this->once())->method("addDefaultActions");

        $character = $this->createStub(Character::class);
        $character->method(PropertyHook::get("level"))->willReturn(12);

        $stage = $this->createMock(Stage::class);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.askExperience", $paragraphs[0]->id);
            $this->assertSame("You still need some experience", $paragraphs[0]->text);
            $this->assertSame("Miyaura", $paragraphs[0]->context["campLeader"]);
        });

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "askExperience" => "You still need some experience",
            ],
            "campLeader" => "Miyaura",
        ]);

        $action = $this->createStub(Action::class);

        $trainingTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        $trainingTemplate->method(PropertyHook::get("action"))->willReturn($action);
        $trainingTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        $trainingTemplate->method(PropertyHook::get("character"))->willReturn($character);

        $trainingTemplate->askAction();
    }

    public function testChallengeMasterIfCharacterHasAlreadySeenHim()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "seenMaster" => "You have already seen your master.",
            ],
            "campLeader" => "Miyaura",
        ]);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.seenMaster", $paragraphs[0]->id);
            $this->assertSame("You have already seen your master.", $paragraphs[0]->text);
            $this->assertSame("Miyaura", $paragraphs[0]->context["campLeader"]);
        });

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster"],
            masterRepository: $masterRepository,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(true);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testChallengeMasterIfCharacterHasNotEnoughExperience()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.absoluteDefeat", $paragraphs[0]->id);
            $this->assertSame("You have absolutely been defeated.", $paragraphs[0]->text);
            $this->assertSame("Heiri", $paragraphs[0]->context["campLeader"]);
        });

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "absoluteDefeat" => "You have absolutely been defeated.",
            ],
            "campLeader" => "Heiri",
        ]);

        $stats = $this->getStatsHandler(
            hasExperience: 40,
            hasRequiredExperience: 100,
            getExperienceIsRequired: true,
            getRequiredExperienceIsRequired: true,
            requiredCharacter: $character,
        );

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster", "setSeenMaster"],
            masterRepository: $masterRepository,
            stats: $stats,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(false);
        $trainingTemplate->expects($this->once())->method("setSeenMaster")->with($character);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testChallengeMasterIfCharacterHasEnoughExperienceButNoAttachmentExists()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.attachmentDisappeared", $paragraphs[0]->id);
            $this->assertStringStartsWith("Your maser suddenly sheats his weapon and disappears, his intentions unclear.", $paragraphs[0]->text);
            $this->assertCount(0, $paragraphs[0]->context);
        });

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "absoluteDefeat" => "You have absolutely been defeated.",
            ],
            "campLeader" => "Heiri",
        ]);

        $stats = $this->getStatsHandler(
            hasExperience: 100,
            hasRequiredExperience: 40,
            getExperienceIsRequired: true,
            getRequiredExperienceIsRequired: true,
            requiredCharacter: $character,
        );

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster", "setSeenMaster"],
            masterRepository: $masterRepository,
            stats: $stats,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(false);
        $trainingTemplate->expects($this->never())->method("setSeenMaster")->with($character);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testChallengeMasterIfCharacterHasEnoughExperience()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.fightStarted", $paragraphs[0]->id);
            $this->assertStringContainsString("offers you a healing potion before the fight", $paragraphs[0]->text);
            $this->assertSame(false, $paragraphs[0]->context["healed"]);
        });
        $stage->expects($this->once())->method(PropertyHook::set("actionGroups"))->with([]);

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "campLeader" => "Heiri",
        ]);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        $stats = $this->getStatsHandler(
            hasExperience: 100,
            hasRequiredExperience: 30,
            getExperienceIsRequired: true,
            getRequiredExperienceIsRequired: true,
            requiredCharacter: $character
        );

        $healthHandler = $this->getHealthHandler(
            hasHealth: 100,
            hasMaxHealth: 100,
            getHealthIsRequired: true,
            getMaxHealthIsRequired: true,
            requiredCharacter: $character,
        );

        [$attachmentRepository, $attachment] = $this->getAttachmentRepositoryAndAttachment(
            repositoryAsMock: true,
            findByOneExpectations: $this->once(),
        );

        [$battle, $battleState] = $this->getBattleAndBattleState(
            battleAsMock: true,
            expectBattleStartedWith: $master,
            expectFightActions: [$stage, $scene, ["op" => "fight"]],
        );

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster", "setSeenMaster"],
            attachmentRepository: $attachmentRepository,
            masterRepository: $masterRepository,
            battle: $battle,
            stats: $stats,
            health: $healthHandler,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(false);
        $trainingTemplate->expects($this->never())->method("setSeenMaster")->with($character);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testChallengeMasterIfCharacterHasEnoughExperienceButIsDamaged()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.fightStarted", $paragraphs[0]->id);
            $this->assertStringContainsString("offers you a healing potion before the fight", $paragraphs[0]->text);
            $this->assertSame(true, $paragraphs[0]->context["healed"]);
        });
        $stage->expects($this->once())->method(PropertyHook::set("actionGroups"))->with([]);

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "campLeader" => "Heiri",
        ]);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        $stats = $this->getStatsHandler(
            hasExperience: 100,
            hasRequiredExperience: 30,
            getExperienceIsRequired: true,
            getRequiredExperienceIsRequired: true,
            requiredCharacter: $character
        );

        $healthHandler = $this->getHealthHandler(
            hasHealth: 50,
            hasMaxHealth: 100,
            getHealthIsRequired: true,
            getMaxHealthIsRequired: true,
            forceMock: true,
            requiredCharacter: $character,
        );

        $healthHandler->expects($this->once())->method("heal")->with(null, $character);

        [$attachmentRepository, $attachment] = $this->getAttachmentRepositoryAndAttachment(
            repositoryAsMock: true,
            findByOneExpectations: $this->once(),
        );

        [$battle, $battleState] = $this->getBattleAndBattleState(
            battleAsMock: true,
            expectBattleStartedWith: $master,
            expectFightActions: [$stage, $scene, ["op" => "fight"]],
        );

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster", "setSeenMaster"],
            attachmentRepository: $attachmentRepository,
            masterRepository: $masterRepository,
            battle: $battle,
            stats: $stats,
            health: $healthHandler,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(false);
        $trainingTemplate->expects($this->never())->method("setSeenMaster")->with($character);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testChallengeMasterIfNoMasterCanBeFound()
    {
        [$masterRepository, $master] = $this->getMasterRepositoryAndMaster(
            repositoryAsMock: true,
            getByLevelExpectations: $this->once(),
            masterIsNone: true,
        );

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.maxLevelReached", $paragraphs[0]->id);
            $this->assertStringContainsString("Max level was reached.", $paragraphs[0]->text);
        });
        $stage->expects($this->never())->method(PropertyHook::set("actionGroups"));

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "campLeader" => "Heiri",
            "text" => [
                "maxLevelReached" => "Max level was reached.",
            ]
        ]);

        $character->method(PropertyHook::get("level"))->willReturn(12);

        [$battle, $battleState] = $this->getBattleAndBattleState(
            battleAsMock: true,
        );

        $battle->expects($this->never())->method("start");
        $battle->expects($this->never())->method("addFightActions");

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["getSeenMaster", "setSeenMaster"],
            masterRepository: $masterRepository,
            battle: $battle,
            scene: $scene,
            stage: $stage,
            character: $character,
        );

        $trainingTemplate->expects($this->once())->method("getSeenMaster")->willReturn(false);
        $trainingTemplate->expects($this->never())->method("setSeenMaster")->with($character);

        // This is what we test
        $trainingTemplate->challengeAction();
    }

    public function testOnFightWon()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "campLeader" => "Heinz",
        ]);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["levelUp", "setSeenMaster"],
            logger: $loggerMock,
            character: $character,
        );

        $stageParameterEvent = $this->getStubBuilder(SimpleStageParameterEvent::class)
            ->setConstructorArgs([
                $stage, $action, $scene, [],
            ])
            ->getStub();

        $battleState = $this->getStubBuilder(BattleState::class)
            ->setConstructorArgs([
                $this->createStub(FighterInterface::class), $this->createStub(FighterInterface::class),
            ])
            ->getStub();

        // Expectations
        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.onFightWon", $paragraphs[0]->id);
            $this->assertStringContainsString("You have defeated", $paragraphs[0]->text);

            $this->assertSame("Heinz", $paragraphs[0]->context["campLeader"]);
        });

        $trainingTemplate->expects($this->once())->method("levelUp")->with($character, null, $stage);
        $trainingTemplate->expects($this->once())->method("setSeenMaster")->with($character, false);

        $loggerMock->expects($this->once())->method("debug");

        // This is what we test
        $trainingTemplate->onFightWon($stageParameterEvent, $battleState);
    }

    public function testOnFightLost()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "campLeader" => "Heinz",
        ]);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            mockedMethods: ["levelUp", "setSeenMaster"],
            logger: $loggerMock,
            character: $character,
        );

        $stageParameterEvent = $this->getStubBuilder(SimpleStageParameterEvent::class)
            ->setConstructorArgs([
                $stage, $action, $scene, [],
            ])
            ->getStub();

        $battleState = $this->getStubBuilder(BattleState::class)
            ->setConstructorArgs([
                $this->createStub(FighterInterface::class), $this->createStub(FighterInterface::class),
            ])
            ->getStub();

        // Expectations
        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(function (array $paragraphs) {
            $this->assertCount(1, $paragraphs);
            $this->assertSame("lotgd2.paragraph.trainingTemplate.onFightLost", $paragraphs[0]->id);
            $this->assertStringContainsString("You have been defeated", $paragraphs[0]->text);

            $this->assertSame("Heinz", $paragraphs[0]->context["campLeader"]);
        });

        $trainingTemplate->expects($this->never())->method("levelUp");
        $trainingTemplate->expects($this->once())->method("setSeenMaster")->with($character, true);

        $loggerMock->expects($this->once())->method("debug");

        // This is what we test
        $trainingTemplate->onFightLost($stageParameterEvent, $battleState);
    }

    public function testGetSeenMasterReturnsFalseIfgetPropertyReturnsFalse()
    {
        $character = $this->createMock(Character::class);
        $character
            ->expects($this->once())
            ->method("getProperty")
            ->with(TrainingTemplate::SeenMasterProperty, false)
            ->willReturn(false);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate();

        $this->assertFalse($trainingTemplate->getSeenMaster($character));
    }

    public function testGetSeenMasterReturnsTrueIfgetPropertyReturnsTrue()
    {
        $character = $this->createMock(Character::class);
        $character
            ->expects($this->once())
            ->method("getProperty")
            ->with(TrainingTemplate::SeenMasterProperty, false)
            ->willReturn(true);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate();

        $this->assertTrue($trainingTemplate->getSeenMaster($character));
    }

    public function testIfSetSeenMasterCallsSetPropertyProperlyIfSeenMasterIsSetToTrue()
    {
        $character = $this->createMock(Character::class);
        $character
            ->expects($this->once())
            ->method("setProperty")
            ->with(TrainingTemplate::SeenMasterProperty, true);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate();

        $trainingTemplate->setSeenMaster($character, true);
    }

    public function testIfSetSeenMasterCallsSetPropertyProperlyIfSeenMasterIsSetToFalse()
    {
        $character = $this->createMock(Character::class);
        $character
            ->expects($this->once())
            ->method("setProperty")
            ->with(TrainingTemplate::SeenMasterProperty, false);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate();

        $trainingTemplate->setSeenMaster($character, false);
    }

    public function testIfAddDefaultActionsAddsDefaultActions()
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate();

        // Set expectations
        $stage->expects($this->once())
            ->method("addActionGroup")
            ->willReturnCallback(function (ActionGroup $actionGroup) use ($stage) {
                $this->assertSame(TrainingTemplate::ActionGroupTraining, $actionGroup->getId());
                $this->assertCount(2, $actionGroup->actions);

                $foundActions = 0;
                $foundAskAction = false;
                $foundChallengeAction = false;

                foreach ($actionGroup->actions as $actionId => $action) {
                    $this->assertInstanceOf(Action::class, $action);

                    if ($action->reference === TrainingTemplate::ActionQuestion and $foundAskAction === false) {
                        $foundAskAction = true;
                        $foundActions += 1;

                        $this->assertSame(["op" => "ask"], $action->getParameters());
                    }

                    if ($action->reference === TrainingTemplate::ActionChallenge and $foundChallengeAction === false) {
                        $foundChallengeAction = true;
                        $foundActions += 1;

                        $this->assertSame(["op" => "challenge"], $action->getParameters());
                    }
                }

                $this->assertTrue($foundAskAction, message: "Ask Action was not found");
                $this->assertTrue($foundChallengeAction, message: "Ask Action was not found");
                $this->assertSame(2, $foundActions, message: "2 different actions were expected but not found");

                return $stage;
            })
        ;

        // This is what we test
        $trainingTemplate->addDefaultActions($stage, $scene);
    }

    public function testIfAddDefaultActionsAddsCheatIfRoleIsGranted()
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);
        $security = $this->createMock(Security::class);

        // Set expectations
        $security
            ->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(true);

        $count = 0;
        $stage->expects($this->exactly(2))
            ->method("addActionGroup")
            ->willReturnCallback(function (ActionGroup $actionGroup) use ($stage, &$count) {
                $count += 1;

                if ($count < 2) {
                    return $stage;
                }

                $this->assertSame(TrainingTemplate::ActionGroupCheats, $actionGroup->getId());
                $this->assertCount(3, $actionGroup->actions);

                $foundActions = 0;
                $foundUnseeMasterCheat = false;
                $foundLevelUpCheat = false;
                $foundLevelTo15Cheat = false;

                foreach ($actionGroup->actions as $actionId => $action) {
                    $this->assertInstanceOf(Action::class, $action);

                    if ($action->reference === TrainingTemplate::ActionCheatUnseeMaster and $foundUnseeMasterCheat === false) {
                        $foundUnseeMasterCheat = true;
                        $foundActions += 1;

                        $this->assertSame(["op" => "cheat", "what" => "unseeMaster"], $action->getParameters());
                    }

                    if ($action->reference === TrainingTemplate::ActionCheatLevelUp and $foundLevelUpCheat === false) {
                        $foundLevelUpCheat = true;
                        $foundActions += 1;

                        $this->assertSame(["op" => "cheat", "what" => "levelUp"], $action->getParameters());
                    }

                    if ($action->reference === TrainingTemplate::ActionCheatSetLevelTo15 and $foundLevelTo15Cheat === false) {
                        $foundLevelTo15Cheat = true;
                        $foundActions += 1;

                        $this->assertSame(["op" => "cheat", "what" => "level15"], $action->getParameters());
                    }
                }

                $this->assertTrue($foundUnseeMasterCheat, message: "Cheat action 'Unsee Master' was not found");
                $this->assertTrue($foundLevelUpCheat, message: "Cheat action 'LevelUp' Action was not found");
                $this->assertTrue($foundLevelTo15Cheat, message: "Cheat action 'level15' Action was not found");
                $this->assertSame(3, $foundActions, message: "3 different actions were expected but not found");

                return $stage;
            })
        ;

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            security: $security,
        );

        // This is what we test
        $trainingTemplate->addDefaultActions($stage, $scene);
    }

    public function testHandleUnseeMasterCheat()
    {
        $character = $this->createStub(Character::class);

        /** @var TrainingTemplate&MockObject $trainingTemplate */
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            ["setSeenMaster"]
        );

        $trainingTemplate->expects($this->once())
            ->method("setSeenMaster")
            ->with($this->identicalTo($character), $this->isFalse());

        $trainingTemplate->handleCheats($character, "unseeMaster");
    }

    public function testHandleLevelUpCheat()
    {
        $character = $this->createStub(Character::class);

        /** @var TrainingTemplate&MockObject $trainingTemplate */
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            ["levelUp"]
        );

        $trainingTemplate->expects($this->once())
            ->method("levelUp")
            ->with($this->identicalTo($character), $this->isNull(), $this->anything());

        $trainingTemplate->handleCheats($character, "levelUp");
    }

    public function testHandleLevel15Cheat()
    {
        $character = $this->createStub(Character::class);

        /** @var TrainingTemplate&MockObject $trainingTemplate */
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            ["levelUp"]
        );

        // Expectations
        $trainingTemplate->expects($this->once())
            ->method("levelUp")
            ->with($this->identicalTo($character), $this->identicalTo(15), $this->anything());

        // This is what we test
        $trainingTemplate->handleCheats($character, "level15");
    }

    #[TestWith([1, 2])]
    #[TestWith([10, 11])]
    #[TestWith([14, 15])]
    #[TestWith([100, 101])]
    public function testLevelUpOneLevelEmitsEventWithCorrectLevelDifferences($initialLevel, $newLevel)
    {
        $character = $this->createMock(Character::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var TrainingTemplate&MockObject $trainingTemplate */
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            eventDispatcher: $eventDispatcher,
        );

        // Expectations
        $character->method(PropertyHook::get("level"))->willReturn($initialLevel);
        $character->expects($this->once())->method(PropertyHook::set("level"))->with($newLevel);

        $eventDispatcher->expects($this->once())
            ->method("dispatch")
            ->willReturnCallback(
                function (CharacterChangeEvent $event, string $eventName) use ($character, $initialLevel, $newLevel) {
                    $this->assertSame(TrainingTemplate::OnCharacterLevelUp, $eventName);
                    $this->assertSame($character, $event->character);
                    $this->assertSame($initialLevel, $event->character->level);

                    return $event;
                }
            );

        // Run
        $trainingTemplate->levelUp($character);
    }

    #[TestWith([1, 15, 14])]
    #[TestWith([10, 15, 5])]
    #[TestWith([14, 15, 1])]
    #[TestWith([100, 15, -85])]
    public function testLevelUpToSpecificLevelEmitsEventWithCorrectLevelDifferences($initialLevel, $newLevel, $deltaLevel)
    {
        $character = $this->createMock(Character::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var TrainingTemplate&MockObject $trainingTemplate */
        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            eventDispatcher: $eventDispatcher,
        );

        // Expectations
        $character->method(PropertyHook::get("level"))->willReturn($initialLevel);
        $character->expects($this->once())->method(PropertyHook::set("level"))->with($newLevel);

        $eventDispatcher->expects($this->once())
            ->method("dispatch")
            ->willReturnCallback(
                function (CharacterChangeEvent $event, string $eventName) use ($character, $initialLevel, $newLevel) {
                    $this->assertSame(TrainingTemplate::OnCharacterLevelUp, $eventName);
                    $this->assertSame($character, $event->character);
                    $this->assertSame($initialLevel, $event->character->level);

                    return $event;
                }
            );

        // Run
        $trainingTemplate->levelUp($character, $newLevel);
    }

    public function testIfAddDefaultActionDoesntAddCheatsIfRoleIsNotGranted()
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(stageAsMock: true);
        $security = $this->createMock(Security::class);

        $trainingTemplate = $this->getPartiallyMockedTrainingTemplate(
            security: $security,
        );

        // Set expectations
        $security
            ->expects($this->once())
            ->method("isGranted")
            ->with("ROLE_CHEATS_ENABLED")
            ->willReturn(false);

        $stage->expects($this->once())
            ->method("addActionGroup")
            ->willReturnSelf()
        ;

        // This is what we test
        $trainingTemplate->addDefaultActions($stage, $scene);
    }

    public function testGetStage(): void
    {
        $stage = $this->createStub(Stage::class);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $trainingTemplate->expects($this->once())
            ->method(PropertyHook::get("stage"))
            ->willReturn($stage);

        $this->assertSame($stage, $trainingTemplate->getStage());
    }

    public function testGetScene(): void
    {
        $scene = $this->createStub(Scene::class);

        $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $trainingTemplate->expects($this->once())
            ->method(PropertyHook::get("scene"))
            ->willReturn($scene);

        $this->assertSame($scene, $trainingTemplate->getScene());
    }

    /**
     * @param int $hasExperience
     * @param int $hasRequiredExperience
     * @param bool $getExperienceIsRequired
     * @param bool $getRequiredExperienceIsRequired
     * @param Character|null $requiredCharacter
     * @return ($getExperienceIsRequired is true ? StatsHandler&MockObject : ($getRequiredExperienceIsRequired is true ? StatsHandler&MockObject : StatsHandler&Stub))
     * @throws Exception
     */
    private function getStatsHandler(
        int $hasExperience,
        int $hasRequiredExperience,
        bool $getExperienceIsRequired = false,
        bool $getRequiredExperienceIsRequired = false,
        ?Character $requiredCharacter = null
    ): StatsHandler {
        if ($getExperienceIsRequired || $getRequiredExperienceIsRequired) {
            $stats = $this->createMock(StatsHandler::class);

            if ($getExperienceIsRequired) {
                $stats
                    ->expects($this->atLeastOnce())
                    ->method("getExperience")
                    ->with($requiredCharacter ?? $this->anything())
                    ->willReturn($hasExperience);
            }

            if ($getRequiredExperienceIsRequired) {
                $stats
                    ->expects($this->atLeastOnce())
                    ->method("getRequiredExperience")
                    ->with($requiredCharacter ?? $this->anything())
                    ->willReturn($hasRequiredExperience);
            }
        } else {
            $stats = $this->createStub(StatsHandler::class);
        }

        if (!$getExperienceIsRequired) {
            $stats
                ->method("getExperience")
                ->willReturn($hasExperience);
        }

        if (!$getRequiredExperienceIsRequired) {
            $stats
                ->method("getRequiredExperience")
                ->willReturn($hasRequiredExperience);
        }

        return $stats;
    }

    /**
     * @param int $hasHealth
     * @param int $hasMaxHealth
     * @param bool $getHealthIsRequired
     * @param bool $getMaxHealthIsRequired
     * @param Character|null $requiredCharacter
     * @return (
     *      $forceMock is true ? HealthHandler&MockObject : (
     *          $getHealthIsRequired is true ? HealthHandler&MockObject : (
     *              $getMaxHealthIsRequired is true ? HealthHandler&MockObject : HealthHandler&Stub
     *          )
     *      )
     *  )
     * @throws Exception
     */
    private function getHealthHandler(
        int $hasHealth,
        int $hasMaxHealth,
        bool $getHealthIsRequired = false,
        bool $getMaxHealthIsRequired = false,
        bool $forceMock = false,
        ?Character $requiredCharacter = null
    ): HealthHandler {
        if ($forceMock || $getHealthIsRequired || $getMaxHealthIsRequired) {
            $stats = $this->createMock(HealthHandler::class);

            if ($getHealthIsRequired) {
                $stats
                    ->expects($this->atLeastOnce())
                    ->method("getHealth")
                    ->with($requiredCharacter ?? $this->anything())
                    ->willReturn($hasHealth);
            }

            if ($getMaxHealthIsRequired) {
                $stats
                    ->expects($this->atLeastOnce())
                    ->method("getMaxHealth")
                    ->with($requiredCharacter ?? $this->anything())
                    ->willReturn($hasMaxHealth);
            }
        } else {
            $stats = $this->createStub(StatsHandler::class);
        }

        if (!$getHealthIsRequired) {
            $stats
                ->method("getHealth")
                ->willReturn($hasHealth);
        }

        if (!$getMaxHealthIsRequired) {
            $stats
                ->method("getMaxHealth")
                ->willReturn($hasMaxHealth);
        }

        return $stats;
    }

    /**
     * @return array{
     *      ($repositoryAsMock is false ? MasterRepository|Stub : MasterRepository|MockObject),
     *      ($masterAsMock is false ? Master|Stub : Master|MockObject),
     *  }
     * @throws Exception
     */
    private function getMasterRepositoryAndMaster(
        bool $repositoryAsMock = false,
        bool $masterAsMock = false,
        ?InvocationOrder $getByLevelExpectations = null,
        string $masterName = "The Master",
        bool $masterIsNone = false,
    ) {
        $repository = $repositoryAsMock ? $this->createMock(MasterRepository::class) : $this->createStub(MasterRepository::class);

        if (!$masterIsNone) {
            $master = $masterAsMock ? $this->createMock(Master::class) : $this->createStub(Master::class);
        } else {
            $master = null;
        }

        if ($getByLevelExpectations) {
            if (!$repositoryAsMock) {
                throw new \InvalidArgumentException("Repository must be mock if findByOneExpectation is given");
            }

            $repository
                ->expects($getByLevelExpectations)
                ->method("getByLevel")
                ->willReturn($master);
        } else {
            $repository
                ->method("getByLevel")
                ->willReturn($master);
        }

        $master?->method(PropertyHook::get("name"))->willReturn($masterName);

        return [$repository, $master];
    }

    /**
     * @return array{
     *      ($repositoryAsMock is false ? AttachmentRepository|Stub : AttachmentRepository|MockObject),
     *      ($attachmentAsMock is false ? Attachment|Stub : Attachment|MockObject),
     *  }
     * @throws Exception
     */
    private function getAttachmentRepositoryAndAttachment(
        bool $repositoryAsMock = false,
        bool $attachmentAsMock = false,
        ?InvocationOrder $findByOneExpectations = null
    ): array {
        $repository = $repositoryAsMock ? $this->createMock(AttachmentRepository::class) : $this->createStub(AttachmentRepository::class);
        $attachment = $attachmentAsMock ? $this->createMock(Attachment::class) : $this->createStub(Attachment::class);

        if ($findByOneExpectations) {
            if (!$repositoryAsMock) {
                throw new \InvalidArgumentException("Repository must be a mock if findByOneExpectations is given.");
            }

            $repository
                ->expects($this->once())
                ->method("findOneBy")
                ->with(["attachmentClass" => BattleAttachment::class])
                ->willReturn($attachment);
        } else {

            $repository
                ->method("findOneBy")
                ->with(["attachmentClass" => BattleAttachment::class])
                ->willReturn($attachment);
        }

        return [
            $repository,
            $attachment,
        ];
    }

    /**
     * @param array{Stage, Scene, array{string: string}}|null $expectFightActions
     * @return array{
     *      ($battleAsMock is false ? Battle|Stub : Battle|MockObject),
     *      ($battleStateAsMock is false ? BattleState|Stub : BattleState|MockObject),
     *  }
     * @throws Exception
     */
    private function getBattleAndBattleState(
        bool $battleAsMock = false,
        bool $battleStateAsMock = false,
        ?Master $expectBattleStartedWith = null,
        ?array $expectFightActions = [],
    ): array {
        $battleState = $battleStateAsMock ? $this->createMock(BattleState::class) : $this->createStub(BattleState::class);
        $battle = $battleAsMock ? $this->createMock(Battle::class) : $this->createStub(Battle::class);

        if ($expectBattleStartedWith) {
            if (!$battleAsMock) {
                throw new \InvalidArgumentException("Battle must be a mock if expectBattleStartedWith is set.");
            }

            $battle
                ->expects($this->once())
                ->method("start")
                ->with($expectBattleStartedWith, $this->anything(), $this->anything(), $this->anything())
                ->willReturn($battleState);
        }

        if ($expectFightActions) {
            if (!$battleAsMock) {
                throw new \InvalidArgumentException("Battle must be a mock if expectFightActions is set.");
            }

            [$stage, $scene, $params] = $expectFightActions;

            if (!$stage instanceof Stage) {
                throw new \InvalidArgumentException("First argument of expectFightActions must be instance of Stage");
            }

            if (!$scene instanceof Scene) {
                throw new \InvalidArgumentException("First argument of expectFightActions must be instance of Stage");
            }

            $battle
                ->expects($this->once())
                ->method("addFightActions")
                ->with($stage, $scene, $battleState, $params);
        }

        return [
            $battle,
            $battleState
        ];
    }

    /**
     * @return array{
     *     ($characterAsMock is false ? Character|Stub : Character|MockObject),
     *     ($sceneAsMock is false ? Scene|Stub : Scene|MockObject),
     *     ($stageAsMock is false ? Stage|Stub : Stage|MockObject),
     *     ($actionAsMock is false ? Action|Stub : Action|MockObject),
     * }
     * @throws Exception
     */
    private function getTemplateEssentials(
        bool $characterAsMock = false,
        bool $sceneAsMock = false,
        bool $stageAsMock = false,
        bool $actionAsMock = false,
    ): array {
        $character = $characterAsMock ? $this->createMock(Character::class) : $this->createStub(Character::class);
        $scene = $sceneAsMock ? $this->createMock(Scene::class) : $this->createStub(Scene::class);
        $stage = $stageAsMock ? $this->createMock(Stage::class) : $this->createStub(Stage::class);
        $action = $actionAsMock ? $this->createMock(Action::class) : $this->createStub(Action::class);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        return [
            $character,
            $scene,
            $stage,
            $action,
        ];
    }

    /**
     * @param array $mockedMethods
     * @param Security|null $security
     * @param LoggerInterface|null $logger
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param AttachmentRepository|null $attachmentRepository
     * @param MasterRepository|null $masterRepository
     * @param Battle|null $battle
     * @param EquipmentHandler|null $equipment
     * @param StatsHandler|null $stats
     * @param HealthHandler|null $health
     * @param GoldHandler|null $gold
     * @param Scene|null $scene
     * @param Stage|null $stage
     * @param Action|null $action
     * @param Character|null $character
     * @return ($mockedMethods is empty ? TrainingTemplate&Stub : TrainingTemplate&MockObject)
     * @throws Exception
     */
    private function getPartiallyMockedTrainingTemplate(
        array $mockedMethods = [],
        ?Security $security = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?AttachmentRepository $attachmentRepository = null,
        ?MasterRepository $masterRepository = null,
        ?Battle $battle = null,
        ?EquipmentHandler $equipment = null,
        ?StatsHandler $stats = null,
        ?HealthHandler $health = null,
        ?GoldHandler $gold = null, // @phpstan-ignore property.onlyWritten
        ?Scene $scene = null,
        ?Stage $stage = null,
        ?Action $action = null,
        ?Character $character = null,
    ): TrainingTemplate {
        if (count($mockedMethods) > 0) {
            $trainingTemplate = $this->getMockBuilder(TrainingTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $security ?? $this->createStub(Security::class),
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
                    $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
                    $masterRepository ?? $this->createStub(MasterRepository::class),
                    $battle ?? $this->createStub(Battle::class),
                    $equipment ?? $this->createStub(EquipmentHandler::class),
                    $stats ?? $this->createStub(StatsHandler::class),
                    $health ?? $this->createStub(HealthHandler::class),
                    $gold ?? $this->createStub(GoldHandler::class),
                ])
                ->getMock();
        } else {
            $trainingTemplate = $this->getStubBuilder(TrainingTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $security ?? $this->createStub(Security::class),
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
                    $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
                    $masterRepository ?? $this->createStub(MasterRepository::class),
                    $battle ?? $this->createStub(Battle::class),
                    $equipment ?? $this->createStub(EquipmentHandler::class),
                    $stats ?? $this->createStub(StatsHandler::class),
                    $health ?? $this->createStub(HealthHandler::class),
                    $gold ?? $this->createStub(GoldHandler::class),
                ])
                ->getStub();
        }

        if ($stage) {
            $trainingTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        }

        if ($scene) {
            $trainingTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        }

        if ($action) {
            $trainingTemplate->method(PropertyHook::get("action"))->willReturn($action);
        }

        if ($character) {
            $trainingTemplate->method(PropertyHook::get("character"))->willReturn($character);
        }

        return $trainingTemplate;
    }
}
