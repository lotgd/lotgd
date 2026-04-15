<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\SimpleFormAttachment;
use LotGD2\Game\Scene\SceneTemplate\BankTemplate;
use LotGD2\Game\Scene\SceneTemplate\DefaultSceneTemplate;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[CoversClass(BankTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
class BankTemplateTest extends TestCase
{
    private readonly BankTemplate $bankTemplate;
    private readonly LoggerInterface&MockObject $logger;
    private readonly Stopwatch&Stub $stopwatch;
    private readonly AttachmentRepository&MockObject $attachmentRepository;
    private readonly SceneRepository&Stub $sceneRepository;
    private readonly DiceBagInterface&Stub $diceBag;
    private readonly ActionService&MockObject $actionService;
    private readonly GoldHandler&MockObject $gold;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->stopwatch = $this->createStub(Stopwatch::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepository::class);
        $this->sceneRepository = $this->createStub(SceneRepository::class);
        $this->diceBag = $this->createStub(DiceBagInterface::class);
        $this->actionService = $this->createMock(ActionService::class);
        $this->gold = $this->createMock(GoldHandler::class);

        $this->bankTemplate = new BankTemplate(
            $this->logger,
            $this->stopwatch,
            $this->attachmentRepository,
            $this->sceneRepository,
            $this->diceBag,
            $this->actionService,
            $this->gold,
        );
    }

    private function getSceneConfig(): array
    {
        return [
            "tellerName" => "Bank Teller",
            "text" => [
                "deposit" => "Gold is deposited",
                "withdraw" => "Gold is withdrawn",
            ]
        ];
    }

    public function testOnSceneChange(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createStub(Character::class);
        $paragraph = $this->createMock(Paragraph::class);
        $stage->method(PropertyHook::get("owner"))->willReturn($character);

        $this->logger
            ->expects($this->atLeast(0))
            ->method("critical");

        $this->attachmentRepository
            ->expects($this->once())
            ->method($this->anything())
            ->willReturn(null);

        $this->actionService
            ->expects($this->never())
            ->method($this->anything());

        $this->gold->expects($this->never())
            ->method($this->anything());

        $sceneConfig = $this->getSceneConfig();

        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))
            ->willReturn($sceneConfig);
        $this->attachmentRepository
            ->expects($this->once())
            ->method($this->anything())
            ->willReturn(null);

        // Assert op is asked for
        $action->expects($this->once())->method("getParameter")->with("op", "")->willReturn("holeduli");

        // Assert debug lines are as expected
        $this->logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Called BankTemplate::onSceneChange, op=holeduli", $this->logger],
                ["Called BankTemplate::defaultAction", $this->logger],
            ]);

        // Assert context is added
        $stage
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get("paragraphs"))
            ->willReturn([
                Stage::SceneText => $paragraph,
            ]);

        $paragraph
            ->expects($this->atLeastOnce())
            ->method("addContext")
            ->willReturnMap([
                ["tellerName", $sceneConfig["tellerName"], $paragraph],
                ["goldInBank", 0, $paragraph],
            ]);

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->onSceneChange();
    }

    public function testOnSceneChangeWhenOpIsDepositOrWithdraw(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createStub(Character::class);

        $stage
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->logger
            ->expects($this->atLeast(0))
            ->method("critical");

        $this->attachmentRepository
            ->expects($this->never())
            ->method($this->anything())
            ->willReturn(null);

        $this->actionService
            ->expects($this->never())
            ->method($this->anything());

        $sceneConfig = $this->getSceneConfig();

        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))
            ->willReturn($sceneConfig);

        $this->gold->expects($this->atLeastOnce())
            ->method($this->anything());

        // Assert op is asked for
        $action
            ->expects($this->once())
            ->method("getParameter")
            ->with("op", "")
            ->willReturn("depositOrWithdraw");

        // Assert debug lines are as expected
        $this->logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Called BankTemplate::onSceneChange, op=holeduli", $this->logger],
                ["Called BankTemplate::depositOrWithdrawAction", $this->logger],
            ]);

        // Assert context is added
        $stage
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs): array {
                $this->assertCount(1, $paragraphs);
                return $paragraphs;
            });

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->onSceneChange();
    }

    public function testdefaultActionWithoutAttachment(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createStub(Scene::class);
        $character = $this->createStub(Character::class);

        $stage->expects($this->atLeastOnce())->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->method(PropertyHook::get("paragraphs"))->willReturn([]);

        $this->attachmentRepository
            ->expects($this->once())
            ->method($this->anything())
            ->willReturn(null);

        $this->actionService
            ->expects($this->never())
            ->method($this->anything());

        $this->gold->expects($this->never())
            ->method($this->anything());

        // Assert debug lines are as expected
        $this->logger
            ->expects($this->atLeastOnce())
            ->method("critical")
            ->with($this->stringStartsWith("Cannot attach attachment"));

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->onSceneChange();
    }


    public function testdefaultActionWithAttachmentAvailable(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createStub(Scene::class);
        $character = $this->createStub(Character::class);
        $attachment = $this->createStub(Attachment::class);

        $stage->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->method(PropertyHook::get("paragraphs"))->willReturn([]);

        $this->logger
            ->expects($this->never())
            ->method("critical");

        $this->attachmentRepository
            ->expects($this->once())
            ->method($this->anything())
            ->willReturn($attachment);

        $this->actionService
            ->expects($this->once())
            ->method("addHiddenAction")
            ->willReturnCallback(function (Stage $s, Action $a) use ($stage) {
                $this->assertSame($stage, $s);
                $this->assertArrayHasKey("op", $a->parameters);
            });

        $this->gold->expects($this->never())
            ->method($this->anything());

        $stage
            ->expects($this->once())
            ->method("addAttachment")
            ->with($attachment);

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->onSceneChange();
    }

    public function testDepositOrWithdrawActionWhenWithdrawIsFalse(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createStub(Character::class);
        $attachment = $this->createStub(Attachment::class);

        $stage
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->logger
            ->expects($this->atLeast(0))
            ->method("critical");

        $this->attachmentRepository
            ->expects($this->never())
            ->method($this->anything())
            ->willReturn(null);

        $this->actionService
            ->expects($this->never())
            ->method($this->anything());

        $this->gold->expects($this->atLeast(1))
            ->method("getGold")
            ->willReturn(200, 100)
        ;

        $sceneConfig = $this->getSceneConfig();

        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))
            ->willReturn($sceneConfig);

        $action->
            expects($this->atLeastOnce())
            ->method("getParameters")
            ->willReturn([
                SimpleFormAttachment::ActionParameterName => [
                    "withdraw" => false,
                    "amount" => 100,
                ],
            ]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs): array {
                $this->assertCount(1, $paragraphs);
                $this->assertInstanceOf(Paragraph::class, $paragraphs[0]);

                $this->assertSame("lotgd2.paragraph.bankTemplate.deposit", $paragraphs[0]->id);
                $this->assertSame("Gold is deposited", $paragraphs[0]->text);
                $this->assertSame(100, $paragraphs[0]->context["amount"]);
                $this->assertSame(100, $paragraphs[0]->context["goldInHand"]);
                $this->assertSame(0, $paragraphs[0]->context["goldInBank"]);
                return $paragraphs;
            });

        $this->logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Deposited 100 gold to the bank (bank account name: defaults)"],
            ]);

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->depositOrWithdrawAction();
    }

    public function testDepositOrWithdrawActionWhenWithdrawIsTrue(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createStub(Character::class);
        $attachment = $this->createStub(Attachment::class);

        $stage
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->logger
            ->expects($this->atLeast(0))
            ->method("critical");

        $this->attachmentRepository
            ->expects($this->never())
            ->method($this->anything())
            ->willReturn(null);

        $this->actionService
            ->expects($this->never())
            ->method($this->anything());

        $this->gold->expects($this->atLeast(1))
            ->method("getGold")
            ->willReturn(200)
        ;

        $sceneConfig = $this->getSceneConfig();

        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))
            ->willReturn($sceneConfig);

        $action
            ->expects($this->atLeastOnce())
            ->method("getParameters")
            ->willReturn([
                SimpleFormAttachment::ActionParameterName => [
                    "withdraw" => true,
                    "amount" => 100,
                ],
            ]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs): array {
                $this->assertCount(1, $paragraphs);
                $this->assertInstanceOf(Paragraph::class, $paragraphs[0]);

                $this->assertSame("lotgd2.paragraph.bankTemplate.withdraw", $paragraphs[0]->id);
                $this->assertSame("Gold is withdrawn", $paragraphs[0]->text);
                $this->assertSame(0, $paragraphs[0]->context["amount"]);
                $this->assertSame(200, $paragraphs[0]->context["goldInHand"]);
                $this->assertSame(0, $paragraphs[0]->context["goldInBank"]);
                return $paragraphs;
            });

        $this->logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Deposited 100 gold to the bank (bank account name: defaults)"],
            ]);

        $this->bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $this->bankTemplate->depositOrWithdrawAction();
    }
}
