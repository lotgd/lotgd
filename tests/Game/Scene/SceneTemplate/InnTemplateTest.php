<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\DataObject\ValueRange;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\InnTemplateType;
use LotGD2\Game\Handler\CharmHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneTemplate\InnTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(InnTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(InnFlirtOption::class)]
#[UsesClass(InnTemplateType::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Stage::class)]
#[UsesClass(ValueRange::class)]
class InnTemplateTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame([
            "chat" => "lotgd2_actionGroup_innTemplate_chat",
            "femalePatron" => "lotgd2_actionGroup_innTemplate_femalePatron",
            "malePatron" => "lotgd2_actionGroup_innTemplate_malePatron",
        ], InnTemplate::ActionGroup);

        $this->assertSame([
            "femalePatron" => "lotgd2_action_innTemplate_patron_female",
            "femalePatronGossip" => "lotgd2_action_innTemplate_femalePatron_gossip",
            "femalePatronJudge" => "lotgd2_action_innTemplate_femalePatron_judge",
            "femalePatronFlirt" => "lotgd2_action_innTemplate_femalePatron_flirt",
            "malePatron" => "lotgd2_action_innTemplate_patron_male",
            "malePatronGossip" => "lotgd2_action_innTemplate_malePatron_gossip",
            "malePatronJudge" => "lotgd2_action_innTemplate_malePatron_judge",
            "malePatronFlirt" => "lotgd2_action_innTemplate_malePatron_flirt",
            "innKeeper" => "lotgd2_action_innTemplate_patron_innKeeper",
            "otherPatrons" => "lotgd2_action_innTemplate_patron_others",
        ], InnTemplate::Action);

        $this->assertSame([
            "otherBanter" => "lotgd2_paragraph_inn_banter_others",
            "femaleBanter" => "lotgd2_paragraph_inn_female_banter",
            "maleBanter" => "lotgd2_paragraph_inn_male_banter",
            "exhausted" => "lotgd2_paragraph_inn_exhausted",
        ], InnTemplate::Paragraphs);

        $this->assertSame("lotgd2_property_innTemplate_seenMaster", InnTemplate::SeenLoverProperty);
    }

    #[TestWith([null, 1, 0, 0, 0])]
    #[TestWith(["otherPatrons", 0, 1, 0, 0])]
    #[TestWith(["femalePatron", 0, 0, 1, 0])]
    #[TestWith(["malePatron", 0, 0, 0, 1])]
    #[TestWith(["unknown", 1, 0, 0, 0])]
    public function testOnSceneChangeCallsExpectedSubMethod(
        ?string $op,
        int $defaultActionCalls,
        int $otherPatronsActionCalls,
        int $femalePatronActionCalls,
        int $malePatronActionCalls,
    ): void {
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("op")->willReturn($op);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug")->with("Called InnTemplate::onSceneChange, op={$op}");

        /** @var InnTemplate&MockObject $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            mockedMethods: ["defaultAction", "otherPatronsAction", "femalePatronAction", "malePatronAction"],
            logger: $logger,
            action: $action,
        );

        $innTemplate->expects($this->exactly($defaultActionCalls))->method("defaultAction");
        $innTemplate->expects($this->exactly($otherPatronsActionCalls))->method("otherPatronsAction");
        $innTemplate->expects($this->exactly($femalePatronActionCalls))->method("femalePatronAction");
        $innTemplate->expects($this->exactly($malePatronActionCalls))->method("malePatronAction");

        $innTemplate->onSceneChange();
    }

    public function testGetDefaultContextReturnsConfiguredValuesAndPicksRandomBanter(): void
    {
        $config = [
            "innKeeper" => "Mira",
            "innKeeperBanter" => "war,trade",
            "malePatron" => [
                "name" => "Eldric",
                "comment" => "Sings loudly",
            ],
            "femalePatron" => [
                "name" => "Lysa",
                "comment" => "Watches quietly",
            ],
        ];

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["war", "trade"])->willReturn(["trade"]);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $innTemplate = $this->getPartiallyMockedInnTemplate(diceBag: $diceBag, scene: $scene);

        $this->assertSame([
            "patron" => [
                "male" => "Eldric",
                "female" => "Lysa",
            ],
            "patronBanter" => [
                "male" => "Sings loudly",
                "female" => "Watches quietly",
            ],
            "innKeeper" => "Mira",
            "innKeeperBanter" => "trade",
        ], $innTemplate->getDefaultContext());
    }

    #[TestWith([true, "Flirt with Violet"])]
    #[TestWith([false, "Chat with Violet"])]
    public function testAddDefaultActionsCreatesExpectedActionGroup(bool $prefersFemale, string $expectedFemaleActionTitle): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([
            "innKeeper" => "Mira",
            "malePatron" => ["name" => "Seth"],
            "femalePatron" => ["name" => "Violet"],
        ]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn($prefersFemale);

        /** @var InnTemplate&Stub $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            genderHandler: $genderHandler,
            scene: $scene,
            character: $character,
        );
        $innTemplate->method(PropertyHook::get("stage"))->willReturn($stage);

        $innTemplate->addDefaultActions();

        $this->assertCount(1, $stage->actionGroups);
        $actionGroup = $stage->actionGroups[InnTemplate::ActionGroup["chat"]] ?? null;
        $this->assertInstanceOf(ActionGroup::class, $actionGroup);
        $this->assertSame("Things to do", $actionGroup->getTitle());

        $femaleAction = $actionGroup->getActionByReference(InnTemplate::Action["femalePatron"]);
        $this->assertInstanceOf(Action::class, $femaleAction);
        $this->assertSame($expectedFemaleActionTitle, $femaleAction->title);

        $maleAction = $actionGroup->getActionByReference(InnTemplate::Action["malePatron"]);
        $this->assertInstanceOf(Action::class, $maleAction);
        $this->assertSame("Talk to Seth the Bard", $maleAction->title);

        $otherAction = $actionGroup->getActionByReference(InnTemplate::Action["otherPatrons"]);
        $this->assertInstanceOf(Action::class, $otherAction);
        $this->assertSame("Converse with patrons", $otherAction->title);

        $innKeeperAction = $actionGroup->getActionByReference(InnTemplate::Action["innKeeper"]);
        $this->assertInstanceOf(Action::class, $innKeeperAction);
        $this->assertSame("Talk to Mira the Barkeep", $innKeeperAction->title);
    }

    public function testDefaultActionSetsSceneTextContextAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $sceneTextParagraph = new Paragraph(id: Stage::SceneText, text: "start");
        $stage = new Stage(owner: $character, paragraphs: [$sceneTextParagraph]);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["war", "trade"])->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        /** @var InnTemplate&MockObject $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            scene: $scene,
            character: $character,
        );
        $innTemplate->method(PropertyHook::get("stage"))->willReturn($stage);

        $innTemplate->defaultAction();

        $this->assertSame("war", $stage->paragraphs[Stage::SceneText]->context["innKeeperBanter"]);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testOtherPatronsActionReplacesParagraphAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        /** @var InnTemplate&MockObject $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            scene: $scene,
            character: $character,
        );
        $innTemplate->method(PropertyHook::get("stage"))->willReturn($stage);

        $innTemplate->otherPatronsAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["otherBanter"], $stage->paragraphs);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testFemalePatronActionGossipSetsParagraphAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn("gossip");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame("Gossip", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testFemalePatronActionJudgeSetsParagraphAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn("judge");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame("Judge", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testFemalePatronActionFlirtActDispatchesToFlirtAction(): void
    {
        $action = $this->createMock(Action::class);
        $action->expects($this->exactly(2))->method("getParameter")
            ->willReturnMap([
                ["act", null, "flirt"],
                ["flirt", null, 2],
            ]);

        /** @var InnTemplate&MockObject $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            mockedMethods: ["flirtAction", "addDefaultActions"],
            action: $action,
            scene: $this->createStub(Scene::class),
        );
        $innTemplate->expects($this->once())->method("flirtAction")->with("female", 2);
        $innTemplate->expects($this->once())->method("addDefaultActions");

        $innTemplate->femalePatronAction();
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testFemalePatronActionBuildsFlirtOptionsWhenPartnerPreferenceIsFemale(bool $seenLover): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(InnTemplate::SeenLoverProperty, false)
            ->willReturn($seenLover);

        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("female");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(true);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        if ($seenLover) {
            $this->assertSame("Violet smiles warmly at you, but you already have a lover.", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
            $this->assertCount(1, $stage->actionGroups);
            $this->assertArrayNotHasKey(InnTemplate::ActionGroup["femalePatron"], $stage->actionGroups);
        } else {
            $this->assertSame("Flirty introduction", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
            $this->assertCount(2, $stage->actionGroups);
            $flirtGroup = $stage->actionGroups[InnTemplate::ActionGroup["femalePatron"]] ?? null;
            $this->assertInstanceOf(ActionGroup::class, $flirtGroup);
            $this->assertCount(2, $flirtGroup->getActions());
        }
    }

    public function testFemalePatronActionFlirtWithoutFlirtsDoesNotAddActionGroup(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(InnTemplate::SeenLoverProperty, false)
            ->willReturn(false);

        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $config = $this->getBaseTemplateConfig();
        $config["femalePatron"]["flirts"] = [];

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("female");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(true);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertArrayNotHasKey(InnTemplate::ActionGroup["femalePatron"], $stage->actionGroups);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testFemalePatronActionBuildsFriendChatOptionsWhenPartnerPreferenceIsNotFemale(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("male");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $friendGroup = $stage->actionGroups[InnTemplate::ActionGroup["femalePatron"]] ?? null;
        $this->assertInstanceOf(ActionGroup::class, $friendGroup);
        $this->assertCount(2, $friendGroup->getActions());
    }

    public function testMalePatronActionGossipSetsParagraphAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn("gossip");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            action: $action,
            scene: $scene,
            stage: $stage,
            character: $character,
            genderHandler: $genderHandler,
            diceBag: $diceBag,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        $this->assertSame("Seth shares bard gossip", $stage->paragraphs[InnTemplate::Paragraphs["maleBanter"]]->text);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testMalePatronActionJudgeSetsParagraphAndAddsDefaultActions(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn("judge");

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            action: $action,
            scene: $scene,
            stage: $stage,
            character: $character,
            genderHandler: $genderHandler,
            diceBag: $diceBag,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        $this->assertSame("Seth evaluates your ballad", $stage->paragraphs[InnTemplate::Paragraphs["maleBanter"]]->text);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testMalePatronActionFlirtActDispatchesToFlirtAction(): void
    {
        $action = $this->createMock(Action::class);
        $action->expects($this->exactly(2))->method("getParameter")
            ->willReturnMap([
                ["act", null, "flirt"],
                ["flirt", null, 1],
            ]);

        /** @var InnTemplate&MockObject $innTemplate */
        $innTemplate = $this->getPartiallyMockedInnTemplate(
            mockedMethods: ["flirtAction", "addDefaultActions"],
            action: $action,
            scene: $this->createStub(Scene::class),
        );
        $innTemplate->expects($this->once())->method("flirtAction")->with("male", 1);
        $innTemplate->expects($this->once())->method("addDefaultActions");

        $innTemplate->malePatronAction();
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testMalePatronActionBuildsFlirtOptionsWhenPartnerPreferenceIsMale(bool $seenLover): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(InnTemplate::SeenLoverProperty, false)
            ->willReturn($seenLover);

        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("male");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            action: $action,
            scene: $scene,
            stage: $stage,
            character: $character,
            genderHandler: $genderHandler,
            diceBag: $diceBag,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        if ($seenLover) {
            $this->assertSame("Seth smiles warmly at you, but you already have a lover.", $stage->paragraphs[InnTemplate::Paragraphs["maleBanter"]]->text);
            $this->assertCount(1, $stage->actionGroups);
            $this->assertArrayNotHasKey(InnTemplate::ActionGroup["malePatron"], $stage->actionGroups);
        } else {
            $this->assertSame("Seth winks at you", $stage->paragraphs[InnTemplate::Paragraphs["maleBanter"]]->text);
            $this->assertCount(2, $stage->actionGroups);
            $flirtGroup = $stage->actionGroups[InnTemplate::ActionGroup["malePatron"]] ?? null;
            $this->assertInstanceOf(ActionGroup::class, $flirtGroup);
            $this->assertCount(2, $flirtGroup->getActions());
        }
    }

    public function testMalePatronActionFlirtWithoutFlirtsDoesNotAddActionGroup(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(InnTemplate::SeenLoverProperty, false)
            ->willReturn(false);

        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $config = $this->getBaseTemplateConfig();
        $config["malePatron"]["flirts"] = [];

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("male");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            action: $action,
            scene: $scene,
            stage: $stage,
            character: $character,
            genderHandler: $genderHandler,
            diceBag: $diceBag,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        $this->assertArrayNotHasKey(InnTemplate::ActionGroup["malePatron"], $stage->actionGroups);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testMalePatronActionBuildsFriendChatOptionsWhenPartnerPreferenceIsNotMale(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn(null);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn("female");
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(true);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            action: $action,
            scene: $scene,
            stage: $stage,
            character: $character,
            genderHandler: $genderHandler,
            diceBag: $diceBag,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        $friendGroup = $stage->actionGroups[InnTemplate::ActionGroup["malePatron"]] ?? null;
        $this->assertInstanceOf(ActionGroup::class, $friendGroup);
        $this->assertCount(2, $friendGroup->getActions());
    }

    #[TestWith(["female"])]
    #[TestWith(["male"])]
    public function testFlirtActionWithMissingOptionLogsCriticalAndReturnsEarly(string $patron): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());
        $scene->method(PropertyHook::get("id"))->willReturn(7);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("critical");

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->once())->method("getCharm")->with($character)->willReturn(5);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            logger: $logger,
            diceBag: $diceBag,
            scene: $scene,
            stage: $stage,
            character: $character,
            charmHandler: $charmHandler,
        );

        $innTemplate->flirtAction($patron, 0);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["{$patron}Banter"], $stage->paragraphs);
        $this->assertSame("You walk a few steps. Suddenly, you forgot what you where about to do.", $stage->paragraphs[InnTemplate::Paragraphs["{$patron}Banter"]]->text);
    }

    public function testFlirtActionSuccessfulAddsCharmAndRemovesTurnsWhenExhaustive(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(2);
        $charmHandler->expects($this->once())->method("addCharm")->with($character, 1);

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("getTurns")->willReturn(3);
        $healthHandler->expects($this->once())->method("addTurns")->with(-2, $character);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            scene: $scene,
            stage: $stage,
            character: $character,
            charmHandler: $charmHandler,
            healthHandler: $healthHandler,
            diceBag: $diceBag,
        );

        $innTemplate->flirtAction("female", 1);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame(1, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
        $this->assertSame(2, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["consumedTurns"]);
    }

    public function testFlirtActionSuccessfulWithNullRewardRangeAddsCharm(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $config = $this->getBaseTemplateConfig();
        $flirtOption = new InnFlirtOption(
            charmRequirement: null,
            name: "Compliment",
            successMessage: "She smiles",
            failureMessage: "Awkward",
            addCharmIfSuccessful: true,
            removeCharmIfFailure: false,
            isExhaustiveIfSuccessful: false,
            charmRewardRange: null,
        );
        $config["femalePatron"]["flirts"][1] = $flirtOption;

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(20);
        $charmHandler->expects($this->once())->method("addCharm")->with($character, 1);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            scene: $scene,
            stage: $stage,
            character: $character,
            charmHandler: $charmHandler,
            diceBag: $diceBag,
        );

        $innTemplate->flirtAction("female", 1);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame(1, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
    }

    public function testFlirtActionSuccessfulDoesNotAddCharmWhenOutsideRewardRange(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(10);
        $charmHandler->expects($this->never())->method("addCharm");

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            scene: $scene,
            stage: $stage,
            character: $character,
            charmHandler: $charmHandler,
            diceBag: $diceBag,
        );

        $innTemplate->flirtAction("female", 1);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame(0, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
    }

    public function testFlirtActionFailureRemovesCharmWhenAllowed(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $config = $this->getBaseTemplateConfig();
        $config["femalePatron"]["flirts"][1] = new InnFlirtOption(
            charmRequirement: 30,
            name: "Impossible",
            successMessage: "not used",
            failureMessage: "No luck",
            addCharmIfSuccessful: false,
            removeCharmIfFailure: true,
            isExhaustiveIfSuccessful: false,
        );

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(5);
        $charmHandler->expects($this->once())->method("addCharm")->with($character, -1);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->with(0.02, 5)->willReturn(false);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            scene: $scene,
            stage: $stage,
            character: $character,
            charmHandler: $charmHandler,
            diceBag: $diceBag,
        );

        $innTemplate->flirtAction("female", 1);
        $this->assertSame("No luck", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
        $this->assertSame(-1, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
    }

    public function testFlirtActionFailureWithNullRemovalRangeRemovesCharm(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $config = $this->getBaseTemplateConfig();
        $config["femalePatron"]["flirts"][1] = new InnFlirtOption(
            charmRequirement: 30,
            name: "Impossible",
            successMessage: "not used",
            failureMessage: "No luck",
            addCharmIfSuccessful: false,
            removeCharmIfFailure: true,
            isExhaustiveIfSuccessful: false,
            charmRemovalRange: null,
        );

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(5);
        $charmHandler->expects($this->once())->method("addCharm")->with($character, -1);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->with(0.02, 5)->willReturn(false);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            charmHandler: $charmHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->flirtAction("female", 1);
        $this->assertSame("No luck", $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
        $this->assertSame(-1, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
    }

    public function testFlirtActionFailureDoesNotRemoveCharmWhenOutsideRemovalRange(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $config = $this->getBaseTemplateConfig();
        $config["femalePatron"]["flirts"][2] = new InnFlirtOption(
            charmRequirement: 10,
            name: "Poem",
            successMessage: "She blushes",
            failureMessage: "She yawns",
            addCharmIfSuccessful: false,
            removeCharmIfFailure: true,
            isExhaustiveIfSuccessful: false,
            charmRemovalRange: new ValueRange(0, 3),
        );

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(5);
        $charmHandler->expects($this->never())->method("addCharm");

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->willReturn(false);
        $diceBag->expects($this->once())->method("pick")->willReturn(["trade"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            charmHandler: $charmHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->flirtAction("female", 2);
        $this->assertSame(0, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->context["rewardedCharm"]);
    }

    public function testFlirtActionForMalePatronSuccessful(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($this->getBaseTemplateConfig());

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(2);
        $charmHandler->expects($this->once())->method("addCharm")->with($character, 1);

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("getTurns")->willReturn(2);
        $healthHandler->expects($this->once())->method("addTurns")->with(-2, $character);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            charmHandler: $charmHandler,
            healthHandler: $healthHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->flirtAction("male", 1);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
    }

    public function testFlirtActionDoesNotChangeCharmOutsideAllowedBoundsAndSkipsTurnLossAtZeroTurns(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $config = $this->getBaseTemplateConfig();
        $config["minCharmPoints"] = 5;
        $config["maxCharmPoints"] = 5;

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->exactly(2))->method("getCharm")->with($character)->willReturn(5);
        $charmHandler->expects($this->never())->method("addCharm");

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())->method("getTurns")->willReturn(0);
        $healthHandler->expects($this->never())->method("addTurns");

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->willReturn(["war"]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            charmHandler: $charmHandler,
            healthHandler: $healthHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->flirtAction("female", 1);

        $this->assertArrayNotHasKey(InnTemplate::Paragraphs["exhausted"], $stage->paragraphs);
    }

    public function testGetDefaultContextUsesFallbacksWhenConfigurationIsMissing(): void
    {
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $innTemplate = $this->getPartiallyMockedInnTemplate(diceBag: $diceBag, scene: $scene);

        $this->assertSame("dragons", $innTemplate->getDefaultContext()["innKeeperBanter"]);
    }

    public function testIsFlirtSuccessfulReturnsTrueWhenCharmRequirementIsMet(): void
    {
        $character = $this->createStub(Character::class);
        $flirtOption = new InnFlirtOption(
            charmRequirement: 5,
            name: "Simple",
            successMessage: "ok",
            failureMessage: "bad",
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug")
            ->with("Check if flirt is successful. Character charm: 5, required charm: 5, delta charm: 0");

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->once())->method("getCharm")->with($character)->willReturn(5);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->never())->method("chance");

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            logger: $logger,
            diceBag: $diceBag,
            charmHandler: $charmHandler,
        );

        $this->assertTrue($innTemplate->isFlirtSuccessful($character, $flirtOption));
    }

    public function testIsFlirtSuccessfulUsesDiceChanceWhenCharmRequirementIsHigher(): void
    {
        $character = $this->createStub(Character::class);
        $flirtOption = new InnFlirtOption(
            charmRequirement: 10,
            name: "Hard",
            successMessage: "ok",
            failureMessage: "bad",
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug")
            ->with("Check if flirt is successful. Character charm: 6, required charm: 10, delta charm: 4");

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->once())->method("getCharm")->with($character)->willReturn(6);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("chance")->with(0.125, 5)->willReturn(true);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            logger: $logger,
            diceBag: $diceBag,
            charmHandler: $charmHandler,
        );

        $this->assertTrue($innTemplate->isFlirtSuccessful($character, $flirtOption));
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testGetSeenLover(bool $expectedSeen): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(InnTemplate::SeenLoverProperty, false)
            ->willReturn($expectedSeen);

        $innTemplate = $this->getPartiallyMockedInnTemplate();
        $this->assertSame($expectedSeen, $innTemplate->getSeenLover($character));
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testSetSeenLover(bool $seen): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("setProperty")
            ->with(InnTemplate::SeenLoverProperty, $seen);

        $innTemplate = $this->getPartiallyMockedInnTemplate();
        $innTemplate->setSeenLover($character, $seen);
    }

    public function testSetSeenLoverDefaultsToTrue(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("setProperty")
            ->with(InnTemplate::SeenLoverProperty, true);

        $innTemplate = $this->getPartiallyMockedInnTemplate();
        $innTemplate->setSeenLover($character);
    }

    public function testDefaultActionWithEmptyConfiguration(): void
    {
        $character = $this->createStub(Character::class);
        $sceneTextParagraph = new Paragraph(id: Stage::SceneText, text: "start");
        $stage = new Stage(owner: $character, paragraphs: [$sceneTextParagraph]);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->defaultAction();

        $this->assertSame("dragons", $stage->paragraphs[Stage::SceneText]->context["innKeeperBanter"]);
        $this->assertSame("Seth", $stage->paragraphs[Stage::SceneText]->context["patron"]["male"]);
        $this->assertSame("Violet", $stage->paragraphs[Stage::SceneText]->context["patron"]["female"]);
        $this->assertCount(1, $stage->actionGroups);
    }

    public function testOtherPatronsActionWithEmptyConfiguration(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(false);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->otherPatronsAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["otherBanter"], $stage->paragraphs);
        $this->assertCount(1, $stage->actionGroups);
    }

    #[TestWith([null, true, true, "/femalePatron.flirt.hasSeenLoverMessage/"])]
    #[TestWith([null, true, false, ""])]
    #[TestWith([null, false, false, ""])]
    #[TestWith(["gossip", false, false, ""])]
    #[TestWith(["judge", false, false, ""])]
    public function testFemalePatronActionWithEmptyConfiguration(?string $act, bool $prefersFemale, bool $seenLover, string $expectedText): void
    {
        if ($act === null && $prefersFemale) {
            $character = $this->createMock(Character::class);
            $character->expects($this->once())->method("getProperty")->with(InnTemplate::SeenLoverProperty, false)->willReturn($seenLover);
        } else {
            $character = $this->createStub(Character::class);
        }
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn($act);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn($prefersFemale);
        if ($act === null) {
            $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn($prefersFemale ? "female" : "male");
        }

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->femalePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
        $this->assertSame($expectedText, $stage->paragraphs[InnTemplate::Paragraphs["femaleBanter"]]->text);
    }

    #[TestWith([null, true, true, "/malePatron.flirt.hasSeenLoverMessage/"])]
    #[TestWith([null, true, false, "/malePatron.flirt.intro/"])]
    #[TestWith([null, false, false, "/malePatron.friend.intro/"])]
    #[TestWith(["gossip", false, false, ""])]
    #[TestWith(["judge", false, false, ""])]
    public function testMalePatronActionWithEmptyConfiguration(?string $act, bool $prefersMale, bool $seenLover, string $expectedText): void
    {
        if ($act === null && $prefersMale) {
            $character = $this->createMock(Character::class);
            $character->expects($this->once())->method("getProperty")->with(InnTemplate::SeenLoverProperty, false)->willReturn($seenLover);
        } else {
            $character = $this->createStub(Character::class);
        }
        $stage = new Stage(owner: $character);
        $action = $this->createMock(Action::class);
        $action->expects($this->once())->method("getParameter")->with("act")->willReturn($act);

        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())->method("prefersFemale")->with($character)->willReturn(!$prefersMale);
        if ($act === null) {
            $genderHandler->expects($this->once())->method("getPreferredPartner")->with($character)->willReturn($prefersMale ? "male" : "female");
        }

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            diceBag: $diceBag,
            genderHandler: $genderHandler,
            action: $action,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->malePatronAction();

        $this->assertArrayHasKey(InnTemplate::Paragraphs["maleBanter"], $stage->paragraphs);
        $this->assertSame($expectedText, $stage->paragraphs[InnTemplate::Paragraphs["maleBanter"]]->text);
    }

    public function testFlirtActionWithEmptyConfiguration(): void
    {
        $character = $this->createStub(Character::class);
        $stage = new Stage(owner: $character);
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);
        $scene->method(PropertyHook::get("id"))->willReturn(1);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("critical");

        $diceBag = $this->createMock(DiceBagInterface::class);
        $diceBag->expects($this->once())->method("pick")->with(["dragons"])->willReturn([]);

        $charmHandler = $this->createMock(CharmHandler::class);
        $charmHandler->expects($this->once())->method("getCharm")->with($character)->willReturn(5);

        $innTemplate = $this->getPartiallyMockedInnTemplate(
            logger: $logger,
            diceBag: $diceBag,
            charmHandler: $charmHandler,
            scene: $scene,
            character: $character,
            stage: $stage,
        );

        $innTemplate->flirtAction("female", 1);

        $this->assertArrayHasKey(InnTemplate::Paragraphs["femaleBanter"], $stage->paragraphs);
    }

    /**
     * @return array<string, mixed>
     */
    private function getBaseTemplateConfig(): array
    {
        return [
            "innKeeper" => "Mira",
            "innKeeperBanter" => "war,trade",
            "minCharmPoints" => 0,
            "maxCharmPoints" => 25,
            "malePatron" => [
                "name" => "Seth",
                "comment" => "Sings loudly",
                "friend" => [
                    "intro" => "Seth greets you warmly",
                    "gossip" => "Seth shares bard gossip",
                    "judge" => "Seth evaluates your ballad",
                ],
                "flirt" => [
                    "intro" => "Seth winks at you",
                    "hasSeenLoverMessage" => "Seth smiles warmly at you, but you already have a lover.",
                ],
                "flirts" => [
                    1 => new InnFlirtOption(
                        charmRequirement: 2,
                        name: "Sing along",
                        successMessage: "He strums in harmony",
                        failureMessage: "You are out of tune",
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: true,
                    ),
                    2 => new InnFlirtOption(
                        charmRequirement: 10,
                        name: "Serenade him",
                        successMessage: "He is touched",
                        failureMessage: "He laughs at you",
                        addCharmIfSuccessful: false,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                    ),
                ],
            ],
            "femalePatron" => [
                "name" => "Violet",
                "comment" => "Watches quietly",
                "friend" => [
                    "intro" => "Friendly conversation",
                    "gossip" => "Gossip",
                    "judge" => "Judge",
                ],
                "flirt" => [
                    "intro" => "Flirty introduction",
                    "hasSeenLoverMessage" => "Violet smiles warmly at you, but you already have a lover.",
                ],
                "flirts" => [
                    1 => new InnFlirtOption(
                        charmRequirement: 2,
                        name: "Compliment",
                        successMessage: "She smiles",
                        failureMessage: "Awkward silence",
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: true,
                    ),
                    2 => new InnFlirtOption(
                        charmRequirement: 10,
                        name: "Poem",
                        successMessage: "She blushes",
                        failureMessage: "She yawns",
                        addCharmIfSuccessful: false,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                    ),
                ],
            ],
        ];
    }

    /**
     * @param array<string> $mockedMethods
     * @param LoggerInterface|null $logger
     * @param DiceBagInterface|null $diceBag
     * @param GenderHandler|null $genderHandler
     * @param CharmHandler|null $charmHandler
     * @param HealthHandler|null $healthHandler
     * @param Action|null $action
     * @param Scene|null $scene
     * @param Character|null $character
     * @param Stage|null $stage
     * @return ($mockedMethods is non-empty-array ? InnTemplate&MockObject : InnTemplate&Stub)
     * @throws Exception
     */
    private function getPartiallyMockedInnTemplate(
        array $mockedMethods = [],
        ?LoggerInterface $logger = null,
        ?DiceBagInterface $diceBag = null,
        ?GenderHandler $genderHandler = null,
        ?CharmHandler $charmHandler = null,
        ?HealthHandler $healthHandler = null,
        ?Action $action = null,
        ?Scene $scene = null,
        ?Character $character = null,
        ?Stage $stage = null,
    ): InnTemplate {
        if (count($mockedMethods) > 0) {
            $innTemplate = $this->getMockBuilder(InnTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $diceBag ?? $this->createStub(DiceBagInterface::class),
                    $genderHandler ?? $this->createStub(GenderHandler::class),
                    $charmHandler ?? $this->createStub(CharmHandler::class),
                    $healthHandler ?? $this->createStub(HealthHandler::class),
                ])
                ->getMock();
        } else {
            $innTemplate = $this->getStubBuilder(InnTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $diceBag ?? $this->createStub(DiceBagInterface::class),
                    $genderHandler ?? $this->createStub(GenderHandler::class),
                    $charmHandler ?? $this->createStub(CharmHandler::class),
                    $healthHandler ?? $this->createStub(HealthHandler::class),
                ])
                ->getStub();
        }

        if ($action) {
            $innTemplate->method(PropertyHook::get("action"))->willReturn($action);
        }

        if ($scene) {
            $innTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        }

        if ($character) {
            $innTemplate->method(PropertyHook::get("character"))->willReturn($character);
        }

        if ($stage) {
            $innTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        }

        return $innTemplate;
    }
}
