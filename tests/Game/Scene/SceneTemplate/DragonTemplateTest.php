<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BasicFighterInterface;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Form\Scene\SceneTemplate\DragonTemplateType;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(DragonTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(DragonTemplateType::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Fighter::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(SimpleStageParameterEvent::class)]
#[UsesClass(CharacterChangeEvent::class)]
class DragonTemplateTest extends TestCase
{
    #[TestWith([null, 1, 0, 0])]
    #[TestWith(["something", 1, 0, 0])]
    #[TestWith(["start", 0, 1, 0])]
    #[TestWith(["epilogue", 0, 0, 1])]
    public function testOnSceneChange(?string $op, int $defaultActionCalls, int $startFightCalls, int $epilogeActionCalls)
    {
        $template = $this->getMockBuilder(DragonTemplate::class)
            ->onlyMethods(["defaultAction", "startFight", "epilogueAction"])
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(SceneRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $this->createStub(ActionService::class),
            ])
            ->getMock();

        $action = $this->createMock(Action::class);
        $action->expects($this->atLeastOnce())->method("getParameter")->with("op")->willReturn($op);

        $template->expects($this->atLeastOnce())->method(PropertyHook::get("action"))->willReturn($action);

        $template->expects($this->exactly($defaultActionCalls))->method("defaultAction");
        $template->expects($this->exactly($startFightCalls))->method("startFight");
        $template->expects($this->exactly($epilogeActionCalls))->method("epilogueAction");

        $template->onSceneChange();
    }

    public function testIfDefaultActionAddsActionToSeekOutTheDragon()
    {
        $template = $this->getStubBuilder(DragonTemplate::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(SceneRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $this->createStub(ActionService::class),
            ])
            ->getStub();

        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("id"))->willReturn(-13);
        $character = $this->createStub(Character::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $stage->expects($this->once())->method("addAction")->willReturnCallback(
            function (string|ActionGroup $actionGroup, Action $action) use ($stage) {
                $this->assertSame("start", $action->getParameter("op"));
                $this->assertSame(-13, $action->sceneId);

                return $stage;
            }
        );

        $template->method(PropertyHook::get("scene"))->willReturn($scene);
        $template->method(PropertyHook::get("stage"))->willReturn($stage);
        $template->method(PropertyHook::get("action"))->willReturn($action);

        $template->defaultAction();
    }

    public function testIfStartFightWithNoValidAttachmentFailsAsExpected()
    {
        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository
            ->expects($this->once())
            ->method("findOneBy")
            ->with(["attachmentClass" => BattleAttachment::class])
            ->willReturn(null);

        $template = $this->getStubBuilder(DragonTemplate::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $attachmentRepository,
                $this->createStub(SceneRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $this->createStub(ActionService::class),
            ])
            ->getStub();

        $stage = $this->createMock(Stage::class);
        $stage->expects($this->once())->method(PropertyHook::set("paragraphs"))->willReturnCallback(
            function (array $paragraphs) {
                $this->assertCount(1, $paragraphs);
                $this->assertSame("lotgd2.paragraph.dragonTemplate.startFightFailed", $paragraphs[0]->id);
            }
        );
        $template->method(PropertyHook::get("stage"))->willReturn($stage);

        $template->startFight();
    }

    public function testIfStartFightWithValidAttachment()
    {
        $battleAttachment = $this->createStub(Attachment::class);

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository
            ->expects($this->once())
            ->method("findOneBy")
            ->with(["attachmentClass" => BattleAttachment::class])
            ->willReturn($battleAttachment);

        $battle = $this->createMock(Battle::class);

        $template = new DragonTemplate(
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $attachmentRepository,
                $this->createStub(SceneRepository::class),
                $battle,
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $this->createStub(ActionService::class),
        );

        $stage = $this->createMock(Stage::class);
        $stage
            ->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(
                function (array $paragraphs) {
                    $this->assertCount(0, $paragraphs);
                }
            );
        $stage
            ->expects($this->once())
            ->method("addAttachment")
            ->willReturnCallback(
                function (Attachment $a, array $config, array $data) use ($battleAttachment, $stage) {
                    $this->assertSame($battleAttachment, $a);
                    $this->assertArrayHasKey("battleState", $data);
                    $this->assertInstanceOf(BattleState::class, $data["battleState"]);

                    return $stage;
                }
            );

        $character = $this->createStub(Character::class);
        $stage
            ->method(PropertyHook::get("owner"))->willReturn($character);
        $stage
            ->expects($this->once())
            ->method("clearActionGroups");

        $scene = $this->createStub(Scene::class);

        $battle
            ->expects($this->once())
            ->method("addFightActions")
            ->with($stage, $scene, $this->isInstanceOf(BattleState::class), ["op" => "fight"]);

        $battleState = $this->createStub(BattleState::class);

        $battle
            ->expects($this->once())
            ->method("start")
            ->willReturnCallback(
                function (BasicFighterInterface $d, ... $kwargs) use ($battleState) {
                    $this->assertFalse($kwargs[3]);
                    return $battleState;
                }
            );

        $template->setSceneChangeParameter($stage, $this->createStub(Action::class), $scene);

        $template->startFight();
    }

    public function testIfStartFightWithValidAttachmentAndConfiguredFightIntro()
    {
        $battleAttachment = $this->createStub(Attachment::class);

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository
            ->expects($this->once())
            ->method("findOneBy")
            ->with(["attachmentClass" => BattleAttachment::class])
            ->willReturn($battleAttachment);

        $battle = $this->createMock(Battle::class);

        $template = $this->getStubBuilder(DragonTemplate::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $attachmentRepository,
                $this->createStub(SceneRepository::class),
                $battle,
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $this->createStub(ActionService::class),
            ])
            ->getStub();

        $stage = $this->createMock(Stage::class);
        $stage
            ->expects($this->once())
            ->method("addAttachment")
            ->willReturnCallback(
                function (Attachment $a, array $config, array $data) use ($battleAttachment, $stage) {
                    $this->assertSame($battleAttachment, $a);
                    $this->assertArrayHasKey("battleState", $data);
                    $this->assertInstanceOf(BattleState::class, $data["battleState"]);

                    return $stage;
                }
            );

        $scene = $this->createMock(Scene::class);
        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "fightIntro" => "A fight intro text",
            ]
        ]);

        $dragon = null;

        $battleState = $this->createStub(BattleState::class);

        $battle
            ->expects($this->once())
            ->method("start")
            ->willReturnCallback(
                function (BasicFighterInterface $d, bool ... $kwargs) use (&$dragon, $battleState) {
                    $this->assertFalse($kwargs[3]);
                    $dragon = $d;
                    return $battleState;
                }
            );

        $battle
            ->expects($this->once())
            ->method("addFightActions") #$this->same(BattleState::class)
            ->willReturnCallback(
                function (Stage $a, Scene $b, BattleState $c, array $d) use ($stage, $scene, $battleState) {
                    $this->assertSame($stage, $a);
                    $this->assertSame($scene, $b);
                    $this->assertSame($battleState, $c);
                    $this->assertSame(["op" => "fight"], $d);
                }
            );


        $stage
            ->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(
                function (array $paragraphs) use (&$dragon, $battle) {
                    $this->assertCount(1, $paragraphs);
                    $this->assertSame("lotgd2.paragraph.dragonTemplate.startFight", $paragraphs[0]->id);
                    $this->assertArrayHasKey("badGuy", $paragraphs[0]->context);
                    $this->assertSame($dragon, $paragraphs[0]->context["badGuy"]);

                    return $battle;
                }
            );

        $template->method(PropertyHook::get("stage"))->willReturn($stage);
        $template->method(PropertyHook::get("scene"))->willReturn($scene);

        $template->startFight();
    }

    public function testOnFightWon()
    {
        $actionService = $this->createMock(ActionService::class);

        $template = $this->getStubBuilder(DragonTemplate::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $this->createStub(LoggerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(AttachmentRepository::class),
                $this->createStub(SceneRepository::class),
                $this->createStub(Battle::class),
                $this->createStub(NewDay::class),
                $this->createStub(GoldHandler::class),
                $this->createStub(StatsHandler::class),
                $this->createStub(DragonCounterHandler::class),
                $actionService,
            ])
            ->getStub();


        $character = $this->createStub(Character::class);
        $stage = $this->createMock(Stage::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $action = $this->createStub(Action::class);
        $scene  = $this->createStub(Scene::class);

        $goodGuy = $this->createStub(FighterInterface::class);
        $badGuy = $this->createStub(Fighter::class);

        $event = $this
            ->getStubBuilder(SimpleStageParameterEvent::class)
            ->setConstructorArgs([
                $stage, $action, $scene, [],
            ])
            ->getStub();

        $battleState = $this->getStubBuilder(BattleState::class)
            ->setConstructorArgs([
                $goodGuy,
                $badGuy,
            ])
            ->getStub();

        $stage
            ->expects($this->once())
            ->method("addAction")
            ->willReturnCallback(function ($actionGroup, Action $action) use ($stage) {
                   $this->assertSame(ActionGroup::EMPTY, $actionGroup);
                   $this->assertSame(["op" => "epilogue"], $action->getParameters());
                   return $stage;
            })
        ;

        $stage
            ->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function ($paragraphs) use ($badGuy) {
                $this->assertCount(1, $paragraphs);

                /** @var Paragraph $paragraph */
                $paragraph = $paragraphs[0];

                $this->assertSame("lotgd2.paragraph.dragonTemplate.fightWon", $paragraph->id);
                $this->assertSame($badGuy, $paragraph->context["badGuy"]);
            })
        ;

        $actionService->expects($this->once())->method("resetActionGroups");

        $template->onFightWon($event, $battleState);
    }

    public function testEpilogueAction()
    {
        $actionService = $this->createMock(ActionService::class);
        $sceneRepository = $this->createMock(SceneRepository::class);

        $template = new DragonTemplate(
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(AttachmentRepository::class),
            $sceneRepository,
            $this->createStub(Battle::class),
            $this->createStub(NewDay::class),
            $this->createStub(GoldHandler::class),
            $this->createStub(StatsHandler::class),
            $this->createStub(DragonCounterHandler::class),
            $actionService,
        );

        $defaultScene = $this->createStub(Scene::class);
        $defaultScene->method(PropertyHook::get("id"))->willReturn(-15);
        $sceneRepository
            ->expects($this->once())
            ->method("getDefaultScene")
            ->willReturn($defaultScene);

        $stage = $this->createMock(Stage::class);
        $character = $this->createStub(Character::class);

        $stage
            ->method(PropertyHook::get("owner"))->willReturn($character);

        $stage
            ->expects($this->once())
            ->method("addAction")
            ->willReturnCallback(function ($actionGroup, Action $action) use ($stage) {
                $this->assertSame(ActionGroup::EMPTY, $actionGroup);
                $this->assertSame(-15, $action->sceneId);
                return $stage;
            })
        ;

        $stage
            ->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->with([]);

        $scene = $this->createStub(Scene::class);

        $actionService->expects($this->once())->method("resetActionGroups");

        $template->setSceneChangeParameter($stage, $this->createStub(Action::class), $scene);
        $template->epilogueAction();
    }

    public function testEpilogueActionWithConfiguredText()
    {
        $actionService = $this->createMock(ActionService::class);
        $sceneRepository = $this->createMock(SceneRepository::class);

        $template = new DragonTemplate(
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(AttachmentRepository::class),
            $sceneRepository,
            $this->createStub(Battle::class),
            $this->createStub(NewDay::class),
            $this->createStub(GoldHandler::class),
            $this->createStub(StatsHandler::class),
            $this->createStub(DragonCounterHandler::class),
            $actionService,
        );

        $defaultScene = $this->createStub(Scene::class);
        $defaultScene->method(PropertyHook::get("id"))->willReturn(-15);
        $sceneRepository
            ->expects($this->once())
            ->method("getDefaultScene")
            ->willReturn($defaultScene);

        $stage = $this->createMock(Stage::class);
        $character = $this->createStub(Character::class);

        $stage
            ->method(PropertyHook::get("owner"))->willReturn($character);

        $stage
            ->expects($this->once())
            ->method("addAction")
            ->willReturnCallback(function ($actionGroup, Action $action) use ($stage) {
                $this->assertSame(ActionGroup::EMPTY, $actionGroup);
                $this->assertSame(-15, $action->sceneId);
                return $stage;
            })
        ;

        $stage
            ->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function ($paragraphs) {
                $this->assertCount(1, $paragraphs);
                $this->assertSame("Epilogue", $paragraphs[0]->text);
            });

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "text" => [
                "epilogue" => "Epilogue",
            ]
        ]);

        $actionService->expects($this->once())->method("resetActionGroups");

        $template->setSceneChangeParameter($stage, $this->createStub(Action::class), $scene);
        $template->epilogueAction();
    }

    public function testIfResetCharacterEmitsEventAsExpected()
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $template = new DragonTemplate(
            $this->createStub(LoggerInterface::class),
            $eventDispatcher,
            $this->createStub(AttachmentRepository::class),
            $this->createStub(SceneRepository::class),
            $this->createStub(Battle::class),
            $this->createStub(NewDay::class),
            $this->createStub(GoldHandler::class),
            $this->createStub(StatsHandler::class),
            $this->createStub(DragonCounterHandler::class),
            $this->createStub(ActionService::class),
        );

        $stage = $this->createStub(Stage::class);
        $character = $this->createMock(Character::class);
        $scene = $this->createStub(Scene::class);

        $character
            ->method(PropertyHook::get("level"))
            ->willReturn(15);

        $character
            ->expects($this->once())
            ->method(PropertyHook::set("level"))
            ->with(1);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (CharacterChangeEvent $event, $eventName) use ($character) {
                $this->assertSame($event->character, $character);
                $this->assertSame(15, $event->characterBefore->level);
                return $event;
            });

        $template->setSceneChangeParameter($stage, $this->createStub(Action::class), $scene);
        $template->resetCharacter();
    }
}
