<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\Scene\SceneTemplate\BankTemplateType;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\WealthHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\SimpleFormAttachment;
use LotGD2\Game\Scene\SceneTemplate\BankTemplate;
use LotGD2\Game\Scene\SceneTemplate\DefaultSceneTemplate;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use LotGD2\Tests\TestTools\HasHandlerRegistryTrait;
use LotGD2\Tests\TestTools\HasStageTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
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
#[UsesClass(BankTemplateType::class)]
#[UsesClass(HealthHandler::class)]
class BankTemplateTest extends TestCase
{
    use HasHandlerRegistryTrait;
    use HasStageTrait;

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

    /**
     * @param LoggerInterface|null $logger
     * @param Stopwatch|null $stopwatch
     * @param AttachmentRepository|null $attachmentRepository
     * @param SceneRepository|null $sceneRepository
     * @param DiceBagInterface|null $diceBag
     * @param ActionService|null $actionService
     * @param WealthHandler|null $wealthHandler
     * @param HealthHandler|null $healthHandler
     * @param bool $asPartialMock
     * @param array $partialMockMethods
     * @return ($asPartialMock is true ? BankTemplate&MockObject : BankTemplate)
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createBankTemplate(
        ?LoggerInterface $logger = null,
        ?StopWatch $stopwatch = null,
        ?AttachmentRepository $attachmentRepository = null,
        ?SceneRepository $sceneRepository = null,
        ?DiceBagInterface $diceBag = null,
        ?ActionService $actionService = null,
        ?WealthHandler $wealthHandler = null,
        ?HealthHandler $healthHandler = null,
        bool $asPartialMock = false,
        array $partialMockMethods = [],
    ) {
        $handlerRegistry = $this->createHandlerRegistry([
            WealthHandler::class => $wealthHandler ?? $this->createStub(WealthHandler::class),
            HealthHandler::class => $healthHandler ?? $this->createStub(HealthHandler::class),
        ]);

        $logger ??= $this->createStub(LoggerInterface::class);
        $stopwatch ??= $this->createStub(StopWatch::class);
        $attachmentRepository ??= $this->createStub(AttachmentRepository::class);
        $sceneRepository ??= $this->createStub(SceneRepository::class);
        $diceBag ??= $this->createStub(DiceBagInterface::class);
        $actionService ??= $this->createStub(ActionService::class);

        if ($asPartialMock) {
            return $this->getMockBuilder(BankTemplate::class)
                ->onlyMethods($partialMockMethods)
                ->setConstructorArgs([
                    $logger,
                    $stopwatch,
                    $attachmentRepository,
                    $sceneRepository,
                    $diceBag,
                    $actionService,
                    $handlerRegistry,
                ])
                ->getMock();
        }

        return new BankTemplate(
            logger: $logger,
            stopwatch: $stopwatch,
            attachmentRepository: $attachmentRepository,
            sceneRepository: $sceneRepository,
            diceBag: $diceBag,
            actionService: $actionService,
            handlerRegistry: $handlerRegistry,
        );
    }

    public function testOnSceneChange(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
            sceneMock: true,
            actionMock: true,
        );

        $paragraph = $this->createMock(Paragraph::class);

        $sceneConfig = $this->getSceneConfig();

        $scene->expects($this->atLeastOnce())->method(PropertyHook::get("templateConfig"))
            ->willReturn($sceneConfig);

        // Assert op is asked for
        $action->expects($this->once())->method("getParameter")->with("op", "")->willReturn("holeduli");

        // Assert debug lines are as expected
        $logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Called BankTemplate::onSceneChange, op=holeduli", $logger],
                ["Called BankTemplate::defaultAction", $logger],
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

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->onSceneChange();
    }

    public function testOnSceneChangeWhenOpIsDepositOrWithdraw(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
            sceneMock: true,
            actionMock: true,
            sceneConfig: $this->getSceneConfig(),
            sceneConfigExpects: $this->atLeastOnce(),
        );

        // Assert op is asked for
        $action
            ->expects($this->once())
            ->method("getParameter")
            ->with("op", "")
            ->willReturn("depositOrWithdraw");

        // Assert debug lines are as expected
        $logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Called BankTemplate::onSceneChange, op=holeduli", $logger],
                ["Called BankTemplate::depositOrWithdrawAction", $logger],
            ]);

        // Assert context is added
        $stage
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs): array {
                $this->assertCount(1, $paragraphs);
                return $paragraphs;
            });

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->onSceneChange();
    }

    public function testDefaultActionWithoutAttachment(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
        );

        $stage->expects($this->atLeastOnce())->method(PropertyHook::get("owner"))->willReturn($character);
        $stage->method(PropertyHook::get("paragraphs"))->willReturn([]);

        // Assert debug lines are as expected
        $logger
            ->expects($this->atLeastOnce())
            ->method("critical")
            ->with($this->stringStartsWith("Cannot attach attachment"));

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->onSceneChange();
    }


    public function testDefaultActionWithAttachmentAvailable(): void
    {
        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $actionService = $this->createMock(ActionService::class);

        $bankTemplate = $this->createBankTemplate(
            attachmentRepository: $attachmentRepository,
            actionService: $actionService,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
        );

        $attachment = $this->createStub(Attachment::class);

        $stage->method(PropertyHook::get("paragraphs"))->willReturn([]);

        $attachmentRepository
            ->expects($this->once())
            ->method($this->anything())
            ->willReturn($attachment);

        $actionService
            ->expects($this->once())
            ->method("addHiddenAction")
            ->willReturnCallback(function (Stage $s, Action $a) use ($stage) {
                $this->assertSame($stage, $s);
                $this->assertArrayHasKey("op", $a->parameters);
            });

        $stage
            ->expects($this->once())
            ->method("addAttachment")
            ->with($attachment);

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->onSceneChange();
    }

    public function testDepositOrWithdrawActionWhenWithdrawIsFalse(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $wealthHandler = $this->createMock(WealthHandler::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
            wealthHandler: $wealthHandler,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
            sceneMock: true,
            actionMock: true,
            sceneConfig: $this->getSceneConfig(),
            sceneConfigExpects: $this->atLeastOnce(),
        );

        $wealthHandler->expects($this->atLeast(1))
            ->method("getGold")
            ->willReturn(200, 100)
        ;

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

        $logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Deposited 100 gold to the bank (bank account name: defaults)"],
            ]);

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->depositOrWithdrawAction();
    }

    public function testDepositOrWithdrawActionWhenWithdrawIsTrue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $wealthHandler = $this->createMock(WealthHandler::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
            wealthHandler: $wealthHandler,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
            sceneMock: true,
            actionMock: true,
            sceneConfig: $this->getSceneConfig(),
            sceneConfigExpects: $this->atLeastOnce(),
        );

        $wealthHandler->expects($this->atLeast(1))
            ->method("getGold")
            ->willReturn(200)
        ;

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

        $logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Deposited 100 gold to the bank (bank account name: defaults)"],
            ]);

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->depositOrWithdrawAction();
    }

    public function testDepositOrWithdrawActionWhenWithdrawIsTrueAndAmountIsZero(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $wealthHandler = $this->createMock(WealthHandler::class);

        $bankTemplate = $this->createBankTemplate(
            logger: $logger,
            wealthHandler: $wealthHandler,
        );

        [$stage, $scene, $action, $character] = $this->getDefaultStageObjects(
            stageMock: true,
            sceneMock: true,
            actionMock: true,
            characterMock: true,
            sceneConfig: $this->getSceneConfig(),
            sceneConfigExpects: $this->atLeastOnce(),
        );

        $character->expects($this->atLeastOnce())
            ->method("getProperty")
            ->with("bank", [])
            ->willReturn(["defaults" => 1000]);
        $character->expects($this->atLeastOnce())
            ->method("setProperty")
            ->with("bank", [
                "defaults" => 0,
            ]);

        $wealthHandler->expects($this->atLeast(1))
            ->method("getGold")
            ->willReturn(200)
        ;

        $action
            ->expects($this->atLeastOnce())
            ->method("getParameters")
            ->willReturn([
                SimpleFormAttachment::ActionParameterName => [
                    "withdraw" => true,
                    "amount" => 0,
                ],
            ]);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs): array {
                $this->assertCount(1, $paragraphs);
                $this->assertInstanceOf(Paragraph::class, $paragraphs[0]);

                $this->assertSame("lotgd2.paragraph.bankTemplate.withdraw", $paragraphs[0]->id);
                $this->assertSame("Gold is withdrawn", $paragraphs[0]->text);
                $this->assertSame(1000, $paragraphs[0]->context["amount"]);
                $this->assertSame(200, $paragraphs[0]->context["goldInHand"]);

                // Our test returns 1000 every call; thus we should expect it to stay at 1000 here, too.
                // Important is that the amount is added to the context!
                $this->assertSame(1000, $paragraphs[0]->context["goldInBank"]);
                return $paragraphs;
            });

        $logger
            ->expects($this->atLeastOnce())
            ->method("debug")
            ->willReturnMap([
                ["Deposited 1000 gold to the bank (bank account name: defaults)"],
            ]);

        $bankTemplate->setSceneChangeParameter($stage, $action, $scene);
        $bankTemplate->depositOrWithdrawAction();
    }

    public function testGoldInBankReturnsNullIfPropertyIsSetButBankBranchIsNot()
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())->method("getProperty")->with("bank", [])->willReturn([
            "branch" => 1000,
        ]);

        /** @var BankTemplate&Stub $bankTemplate */
        $bankTemplate = $this->getStubBuilder(BankTemplate::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getStub();

        $gold = $bankTemplate->getGoldInBank($character, []);
        $this->assertSame(0, $gold);
    }

    public function testNewDayEventWithoutBankScenes()
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $sceneRepository = $this->createMock(SceneRepository::class);

        $bankTemplate = $this->createBankTemplate(
            stopwatch: $stopwatch,
            sceneRepository: $sceneRepository,
            asPartialMock: true,
            partialMockMethods: ["addGoldInBank"]
        );

        $bankTemplate->expects($this->never())->method("addGoldInBank");

        $stopwatch->expects($this->once())->method("start");
        $stopwatch->expects($this->once())->method("stop");

        $sceneRepository->expects($this->once())->method("findBy")->willReturnCallback(
            function (array $filter, array $orderBy) {
                $this->assertArrayHasKey("templateClass", $filter);
                $this->assertSame(BankTemplate::class, $filter["templateClass"]);

                $this->assertArrayHasKey("id", $orderBy);
                $this->assertSame("ASC", $orderBy["id"]);

                return [];
            }
        );

        $stageChangeEvent = $this->createStub(StageChangeEvent::class);

        $bankTemplate->onNewDayEvent($stageChangeEvent);
    }

    public function testNewDayEventWithMultipleBankScenesWithNoRounds()
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createStub(DiceBagInterface::class);

        $bankTemplate = $this->createBankTemplate(
            stopwatch: $stopwatch,
            sceneRepository: $sceneRepository,
            diceBag: $diceBag,
            asPartialMock: true,
            partialMockMethods: ["addGoldInBank", "getGoldInBank"],
        );

        $stopwatch->expects($this->once())->method("start");
        $stopwatch->expects($this->once())->method("stop");

        $diceBag->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            return $min;
        });

        $scenes = [
            $this->createMock(Scene::class),
            $this->createMock(Scene::class),
            $this->createMock(Scene::class),
        ];

        $scenes[0]->expects($this->once())->method(PropertyHook::get("templateConfig"))->willReturn([
            "accountName" => "defaults",
            "minInterest" => 5,
            "maxInterest" => 5,
            "maxGoldInBank" => 10000,
        ]);

        $scenes[0]->method(PropertyHook::get("title"))->willReturn("Bank One");

        $scenes[1]->expects($this->once())->method(PropertyHook::get("templateConfig"))->willReturn([
            "accountName" => "Another Bank",
            "minInterest" => 1,
            "maxInterest" => 1,
            "maxGoldInBank" => 10000,
        ]);

        $scenes[1]->method(PropertyHook::get("title"))->willReturn("Bank Two");

        // Second scene has no accountName set; must return to defaults.
        $scenes[2]->expects($this->once())->method(PropertyHook::get("templateConfig"))->willReturn([
        ]);

        $bankTemplate->expects($this->atLeastOnce())->method("getGoldInBank")->willReturnCallback(
            function (Character $character, array $config) {
                if (!isset($config["accountName"]) or $config["accountName"] === "defaults") {
                    return 1000;
                } else {
                    return 500;
                }
            }
        );

        $bankTemplate->expects($this->exactly(2))->method("addGoldInBank")->willReturnCallback(
            function (Character $character, array $config, int $amount) {
                if ($config["accountName"] === "defaults") {
                    $this->assertSame(50, $amount);
                } else {
                    $this->assertSame(5, $amount);
                }
            }
        );

        $sceneRepository->expects($this->once())->method("findBy")->willReturnCallback(
            function (array $filter, array $orderBy) use ($scenes) {
                $this->assertArrayHasKey("templateClass", $filter);
                $this->assertSame(BankTemplate::class, $filter["templateClass"]);

                $this->assertArrayHasKey("id", $orderBy);
                $this->assertSame("ASC", $orderBy["id"]);

                return $scenes;
            }
        );

        $character = $this->createStub(Character::class);
        $character->method("getProperty")->willReturnCallback(function (string $property, $default = null) {
            if ($property === HealthHandler::Turns) {
                return 0;
            } else {
                return $default;
            }
        });

        $stage = $this->createMock(Stage::class);
        $counter = 0;
        $stage->expects($this->exactly(2))->method("addParagraph")->willReturnCallback(
            function (Paragraph $paragraph) use ($stage, &$counter) {
                if ($counter === 0) {
                    $this->assertSame("Bank One", $paragraph->context["bankName"]);
                } elseif ($counter === 1) {
                    $this->assertSame("Bank Two", $paragraph->context["bankName"]);
                }

                $counter++;

                return $stage;
            }
        );

        $stageChangeEvent = $this->createMock(StageChangeEvent::class);
        $stageChangeEvent->method(PropertyHook::get("characterBefore"))->willReturn($character);
        $stageChangeEvent->expects($this->exactly(2))->method(PropertyHook::get("stage"))->willReturn($stage);

        $bankTemplate->onNewDayEvent($stageChangeEvent);
    }

    #[TestWith([20, 10, 50])]
    #[TestWith([20, 40, 0])]
    #[TestWith([10, 40, 0])]
    public function testNewDayEventWithOneBankScenesWithTooManyRoundsLeft(int $roundsRequired, int $roundsLeft, int $interest)
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createStub(DiceBagInterface::class);

        $healthHandler = $this->createStub(HealthHandler::class);
        $healthHandler->method("getTurns")->willReturn($roundsLeft);

        $bankTemplate = $this->createBankTemplate(
            stopwatch: $stopwatch,
            sceneRepository: $sceneRepository,
            diceBag: $diceBag,
            healthHandler: $healthHandler,
            asPartialMock: true,
            partialMockMethods: ["addGoldInBank", "getGoldInBank"],
        );

        $stopwatch->expects($this->once())->method("start");
        $stopwatch->expects($this->once())->method("stop");

        $diceBag->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            return $min;
        });

        $scenes = [
            $this->createMock(Scene::class),
        ];

        $scenes[0]->expects($this->once())->method(PropertyHook::get("templateConfig"))->willReturn([
            "accountName" => "defaults",
            "minInterest" => 5,
            "maxInterest" => 5,
            "turnsLeftBeforeInterest" => $roundsRequired,
            "maxGoldInBank" => 10000,
        ]);

        $scenes[0]->method(PropertyHook::get("title"))->willReturn("Bank One");

        $bankTemplate->expects($this->atLeastOnce())->method("getGoldInBank")->willReturnCallback(
            function (Character $character, array $config) {
                return 1000;
            }
        );

        $bankTemplate->expects($this->once())->method("addGoldInBank")->willReturnCallback(
            function (Character $character, array $config, int $amount) use ($interest) {
                $this->assertSame($interest, $amount);
            }
        );

        $sceneRepository->expects($this->once())->method("findBy")->willReturnCallback(
            function (array $filter, array $orderBy) use ($scenes) {
                $this->assertArrayHasKey("templateClass", $filter);
                $this->assertSame(BankTemplate::class, $filter["templateClass"]);

                $this->assertArrayHasKey("id", $orderBy);
                $this->assertSame("ASC", $orderBy["id"]);

                return $scenes;
            }
        );

        $character = $this->createStub(Character::class);
        $character->method("getProperty")->willReturnCallback(
            function (string $property, $default = null) use ($roundsLeft) {
                if ($property === HealthHandler::Turns) {
                    return $roundsLeft;
                } else {
                    return $default;
                }
            }
        );

        $stage = $this->createMock(Stage::class);
        $stage->expects($this->once())->method("addParagraph")->willReturnCallback(
            function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("Bank One", $paragraph->context["bankName"]);
                return $stage;
            }
        );

        $stageChangeEvent = $this->createMock(StageChangeEvent::class);
        $stageChangeEvent->method(PropertyHook::get("characterBefore"))->willReturn($character);
        $stageChangeEvent->expects($this->once())->method(PropertyHook::get("stage"))->willReturn($stage);

        $bankTemplate->onNewDayEvent($stageChangeEvent);
    }

    #[TestWith([10000, 10001, 0])]
    #[TestWith([10000, 20000, 0])]
    #[TestWith([10000, 10000, 500])]
    #[TestWith([10000, 1000, 50])]
    #[TestWith([10000, -10001, 0])]
    #[TestWith([10000, -20000, 0])]
    #[TestWith([10000, -10000, -500])]
    #[TestWith([10000, -1000, -50])]
    public function testNewDayEventWithOneBankScenesWithTooMuchMoneyInBank(int $moneyBorder, int $moneyInAccount, int $interest)
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createStub(DiceBagInterface::class);
        $healthHandler = $this->createStub(HealthHandler::class);

        $bankTemplate = $this->createBankTemplate(
            stopwatch: $stopwatch,
            sceneRepository: $sceneRepository,
            diceBag: $diceBag,
            healthHandler: $healthHandler,
            asPartialMock: true,
            partialMockMethods: ["addGoldInBank", "getGoldInBank"],
        );

        $stopwatch->expects($this->once())->method("start");
        $stopwatch->expects($this->once())->method("stop");

        $diceBag->method("pseudoBell")->willReturnCallback(function ($min, $max) {
            return $min;
        });

        $scenes = [
            $this->createMock(Scene::class),
        ];

        $scenes[0]->expects($this->once())->method(PropertyHook::get("templateConfig"))->willReturn([
            "accountName" => "defaults",
            "minInterest" => 5,
            "maxInterest" => 5,
            "turnsLeftBeforeInterest" => 10,
            "maxGoldInBank" => $moneyBorder,
        ]);

        $scenes[0]->method(PropertyHook::get("title"))->willReturn("Bank One");

        $bankTemplate->expects($this->atLeastOnce())->method("getGoldInBank")->willReturnCallback(
            function (Character $character, array $config) use ($moneyInAccount) {
                return $moneyInAccount;
            }
        );

        $bankTemplate->expects($this->once())->method("addGoldInBank")->willReturnCallback(
            function (Character $character, array $config, int $amount) use ($interest) {
                $this->assertSame($interest, $amount);
            }
        );

        $sceneRepository->expects($this->once())->method("findBy")->willReturnCallback(
            function (array $filter, array $orderBy) use ($scenes) {
                $this->assertArrayHasKey("templateClass", $filter);
                $this->assertSame(BankTemplate::class, $filter["templateClass"]);

                $this->assertArrayHasKey("id", $orderBy);
                $this->assertSame("ASC", $orderBy["id"]);

                return $scenes;
            }
        );

        $character = $this->createStub(Character::class);
        $character->method("getProperty")->willReturnCallback(
            function (string $property, $default = null) {
                if ($property === HealthHandler::Turns) {
                    return 0;
                } else {
                    return $default;
                }
            }
        );

        $stage = $this->createMock(Stage::class);
        $stage->expects($this->once())->method("addParagraph")->willReturnCallback(
            function (Paragraph $paragraph) use ($stage, $interest) {
                $this->assertSame("Bank One", $paragraph->context["bankName"]);
                $this->assertEqualsWithDelta(5, $paragraph->context["bankInterestRate"], 0.001);
                $this->assertEquals($interest, $paragraph->context["bankInterest"]);
                return $stage;
            }
        );

        $stageChangeEvent = $this->createMock(StageChangeEvent::class);
        $stageChangeEvent->method(PropertyHook::get("characterBefore"))->willReturn($character);
        $stageChangeEvent->expects($this->once())->method(PropertyHook::get("stage"))->willReturn($stage);

        $bankTemplate->onNewDayEvent($stageChangeEvent);
    }
}
