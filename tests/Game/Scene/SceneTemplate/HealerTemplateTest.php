<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\WealthHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneTemplate\HealerTemplate;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(HealerTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(Paragraph::class)]
#[AllowMockObjectsWithoutExpectations]
class HealerTemplateTest extends TestCase
{
    private function getDefaultTemplateConfig(): array
    {
        return [
            "stealHealth" => true,
            "actionGroupPotionTitle" => "Potions",
            "actionCompleteHealingTitle" => "Complete Healing",
            "text" => [
                "onEntryAndDamaged" => "You need healing. Price is {{ price }} gold.",
                "onEntryAndHealthy" => "You are healthy already.",
                "onEntryAndOverhealed" => "You are overhealed!",
                "onHealEnoughGold" => "You have been healed for {{ amount }} points!",
                "onHealNotEnoughGold" => "Not enough gold! Need {{ price }} gold."
            ]
        ];
    }

    /**
     * @param string[] $mockedMethods
     * @return ($mockHealthHandler is true ? HealerTemplate&MockObject : HealerTemplate&Stub)
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function getPartiallyMockedHealerTemplate(
        array $mockedMethods = [],
        bool $mockHealthHandler = false,
        ?LoggerInterface $logger = null,
        ?Security $security = null,
        ?HandlerRegistry $handlerRegistry = null,
        ?HealthHandler $healthHandler = null,
        ?WealthHandler $wealthHandler = null,
    ): HealerTemplate {
        if ($handlerRegistry === null) {
            $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);
        }

        $constructorArgs = [
            $logger ?? $this->createStub(LoggerInterface::class),
            $security ?? $this->createStub(Security::class),
            $handlerRegistry,
        ];

        if ($mockHealthHandler) {
            return $this->getMockBuilder(HealerTemplate::class)
                ->setConstructorArgs($constructorArgs)
                ->onlyMethods($mockedMethods)
                ->getMock();
        } else {
            /** @var HealerTemplate&Stub $stub */
            $stub = $this->getStubBuilder(HealerTemplate::class)
                ->setConstructorArgs($constructorArgs)
                ->onlyMethods($mockedMethods)
                ->getStub();
            return $stub;
        }
    }

    private function getStubbedHandlerRegistry(
        ?HealthHandler $healthHandler = null,
        ?WealthHandler $wealthHandler = null,
    ): HandlerRegistry {
        $handlerRegistry = $this->createStub(HandlerRegistry::class);
        $handlerRegistry->method("get")
            ->willReturnMap([
                [HealthHandler::class, $healthHandler ?? $this->createStub(HealthHandler::class)],
                [WealthHandler::class, $wealthHandler ?? $this->createStub(WealthHandler::class)],
            ]);
        return $handlerRegistry;
    }

    /**
     * @param string[][]|"never" $actionMap
     * @param array<string, mixed>|bool $templateConfig
     * @return array{Stage&MockObject, Action&MockObject, Scene&MockObject, Character&MockObject}
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function getStageMocks(
        int $characterId = 123,
        int $characterLevel = 5,
        null|array|string $actionMap = null,
        bool|array $templateConfig = true,
    ): array {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        if ($actionMap !== "never") {
            $action->expects($this->atLeastOnce())
                ->method('getParameter')
                ->willReturnMap($actionMap ?? [
                    ["op", null]
                ]);
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

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $character->expects($this->atLeast(0))
            ->method(PropertyHook::get("level"))
            ->willReturn($characterLevel);

        $character->expects($this->atLeast(0))
            ->method(PropertyHook::get("id"))
            ->willReturn($characterId);

        return [$stage, $action, $scene, $character];
    }

    public function testOnSceneChangeWithDefaultAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(50);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method("getGold");

        $stage->expects($this->once())
            ->method("addParagraph")
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onEntryAndDamaged", $paragraph->id);
                $this->assertSame('You need healing. Price is {{ price }} gold.', $paragraph->text);
                $this->assertArrayHasKey("price", $paragraph->context);
                $this->assertIsInt($paragraph->context["price"]);

                return $stage;
            });

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $healerTemplate = new HealerTemplate(
            logger: $logger,
            security: $security,
            handlerRegistry: $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler),
        );

        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->onSceneChange();
    }

    public function testOnSceneChangeWithHealAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: [
                ['op', null, 'heal'],
                ['amount', null, 50],
                ['price', null, 100]
            ]
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['Called HealerTemplate::defaultAction'],
                ['123: Healed by 50 for 100 gold.'],
            ]);

        $security = $this->createStub(Security::class);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertCount(1, $paragraphs);
                $paragraph = $paragraphs[0];

                $this->assertSame("lotgd2.paragraph.healerTemplate.onHealEnoughGold", $paragraph->id);
                $this->assertSame('You have been healed for {{ amount }} points!', $paragraph->text);
                $this->assertEquals([
                    "price" => 100,
                    "amount" => 50
                ], $paragraph->context);

                return $stage;
            });

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('heal')
            ->with($character, 50);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->once())
            ->method('getGold')
            ->willReturn(150);
        $wealthHandler->expects($this->once())
            ->method('addGold')
            ->with($character, -100);

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->onSceneChange();
    }

    public function testOnSceneChangeWithCheatAction(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: [
                ['op', null, 'cheat'],
                ['what', null, 'heal'],
            ]
        );

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('heal')
            ->with($character);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->exactly(2))
            ->method('getHealth')
            ->willReturn(100);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry(healthHandler: $healthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->onSceneChange();
    }

    public function testDefaultActionWhenDead(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: 'never',
            templateConfig: false,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertCount(1, $paragraphs);
                $paragraph = $paragraphs[0];

                $this->assertSame("lotgd2.paragraph.healerTemplate.isDeadMessage", $paragraph->id);
                $this->assertSame("You are dead and cannot get healing from the Healer's hut. Try waiting for a new day to continue playing.", $paragraph->text);
                $this->assertEquals([], $paragraph->context);
            });

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry(healthHandler: $healthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testDefaultActionWhenHealthy(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: "never",
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(100);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method('getGold');

        $stage->expects($this->once())
            ->method('addParagraph')
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onEntryAndHealthy", $paragraph->id);
                $this->assertSame('You are healthy already.', $paragraph->text);
                return $stage;
            });

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry(healthHandler: $healthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testDefaultActionWhenDamaged(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: "never",
            characterLevel: 3,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeast(1))
            ->method('getHealth')
            ->willReturn(50);
        $healthHandler->expects($this->atLeast(1))
            ->method('getMaxHealth')
            ->willReturn(100);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method("addParagraph")
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onEntryAndDamaged", $paragraph->id);
                $this->assertSame('You need healing. Price is {{ price }} gold.', $paragraph->text);
                $this->assertArrayHasKey("price", $paragraph->context);
                $this->assertIsInt($paragraph->context["price"]);

                return $stage;
            });

        $stage->expects($this->once())
            ->method('addActionGroup');

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry(healthHandler: $healthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testDefaultActionWhenOverhealed(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: 'never',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(150);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method('getGold');

        $stage->expects($this->once())
            ->method('addParagraph')
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onEntryAndOverhealed", $paragraph->id);
                $this->assertSame('You are overhealed!', $paragraph->text);
                return $stage;
            });

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testDefaultActionWhenOverhealedButStealHealthDisabled(): void
    {
        $templateConfig = $this->getDefaultTemplateConfig();
        $templateConfig["stealHealth"] = false;

        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: 'never',
            templateConfig: $templateConfig,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(150);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method('getGold');

        $stage->expects($this->once())
            ->method('addParagraph')
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onEntryAndHealthy", $paragraph->id);
                $this->assertSame('You are healthy already.', $paragraph->text);
                return $stage;
            });

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testDefaultActionWithCheats(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: 'never',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())
            ->method('getGold');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(100);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                return $actionGroup->getId() === "lotgd2.actionGroup.healerTemplate.cheats"
                    && $actionGroup->getTitle() === "Cheats"
                    && count($actionGroup->getActions()) === 1;
            }));

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->defaultAction();
    }

    public function testHealActionWithEnoughGold(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterId: 456,
            actionMap: [
                ['amount', null, 30],
                ['price', null, 50],
            ],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['456: Healed by 30 for 50 gold.'],
                ['Called HealerTemplate::defaultAction'],
            ]);

        $security = $this->createMock(Security::class);
        $security->expects($this->never())
            ->method('isGranted');

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->once())
            ->method('getGold')
            ->willReturn(100);
        $wealthHandler->expects($this->once())
            ->method('addGold')
            ->with($character, -50);

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('heal')
            ->with($character, 30);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertCount(1, $paragraphs);
                $paragraph = $paragraphs[0];

                $this->assertSame("lotgd2.paragraph.healerTemplate.onHealEnoughGold", $paragraph->id);
                $this->assertSame('You have been healed for {{ amount }} points!', $paragraph->text);
                $this->assertEquals([
                    "price" => 50,
                    "amount" => 30,
                ], $paragraph->context);

                return $stage;
            });

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->healAction();
    }

    public function testHealActionWithNotEnoughGold(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            actionMap: [
                ['amount', null, 30],
                ['price', null, 50],
            ],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->once())
            ->method('getGold')
            ->willReturn(20);
        $wealthHandler->expects($this->never())
            ->method('addGold');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->never())
            ->method('heal');

        $security = $this->createStub(Security::class);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertSame("lotgd2.paragraph.healerTemplate.onHealNotEnoughGold", $paragraphs[0]->id);
                $this->assertSame('Not enough gold! Need {{ price }} gold.', $paragraphs[0]->text);
                $this->assertSame([
                    "price" => 50,
                ], $paragraphs[0]->context);
                return $stage;
            });

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->healAction();
    }

    public function testHealActionWithFreeHealing(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterId: 789,
            actionMap: [
                ['amount', null, 30],
                ['price', null, 0],
            ],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['Called HealerTemplate::defaultAction'],
                ['789: Healed by 30 for 0 gold.'],
            ]);

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('isGranted');

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($stage) {
                $this->assertCount(1, $paragraphs);
                $paragraph = $paragraphs[0];

                $this->assertSame("lotgd2.paragraph.healerTemplate.onHealEnoughGold", $paragraph->id);
                $this->assertSame('You have been healed for {{ amount }} points!', $paragraph->text);
                $this->assertEquals([
                    "price" => 0,
                    "amount" => 30,
                ], $paragraph->context);

                return $stage;
            });

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('heal')
            ->with($character, 30);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->once())
            ->method('addGold')
            ->with($character, 0);

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->healAction();
    }

    public function testGetPriceCalculation(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('isGranted');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('getMaxHealth')
            ->willReturn(100);
        $healthHandler->expects($this->once())
            ->method('getHealth')
            ->willReturn(60);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())->method('getGold');

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);

        $price = $healerTemplate->getPrice($character);

        $this->assertIsInt($price);
        $this->assertGreaterThanOrEqual(0, $price);

        // Price should be calculated as: log(level) * (maxHealth - currentHealth + 10)
        $expectedPrice = (int)round(log(5) * (100 - 60 + 10));
        $this->assertEquals($expectedPrice, $price);
    }

    public function testGetPriceWithLevelZero(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method(PropertyHook::get("level"))
            ->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('isGranted');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->once())
            ->method('getMaxHealth')
            ->willReturn(100);
        $healthHandler->expects($this->once())
            ->method('getHealth')
            ->willReturn(50);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())->method('getGold');

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);

        $price = $healerTemplate->getPrice($character);

        // With level 0, it should be treated as level 1
        $expectedPrice = (int)round(log(1) * (100 - 50 + 10));
        $this->assertEquals($expectedPrice, $price);
        $this->assertEquals(0, $price); // log(1) = 0
    }

    public function testAddPotionActionsWithFreeHealing(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 1,
            actionMap: 'never',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('isGranted');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(50);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())->method('getGold');

        // For level 1, price should be 0 (free healing)
        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                $actions = $actionGroup->getActions();
                // Should only have complete healing action (no partial options for free healing)
                return count($actions) === 1 
                    && $actionGroup->getId() === HealerTemplate::ActionGroupPotions
                    && $actionGroup->getTitle() === 'Potions';
            }));

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->addPotionActions();
    }

    public function testAddPotionActionsWithPaidHealing(): void
    {
        [$stage, $action, $scene, $character] = $this->getStageMocks(
            characterLevel: 5,
            actionMap: 'never',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');

        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('isGranted');

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);
        $healthHandler->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(20);

        $wealthHandler = $this->createMock(WealthHandler::class);
        $wealthHandler->expects($this->never())->method('getGold');

        // For level 5, price should be > 0
        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                $actions = $actionGroup->getActions();
                // Should have complete healing + partial healing options
                return count($actions) > 1 
                    && $actionGroup->getId() === HealerTemplate::ActionGroupPotions
                    && $actionGroup->getTitle() === 'Potions';
            }));

        $handlerRegistry = $this->getStubbedHandlerRegistry($healthHandler, $wealthHandler);

        $healerTemplate = new HealerTemplate($logger, $security, $handlerRegistry);
        $healerTemplate->setSceneChangeParameter($stage, $action, $scene);
        $healerTemplate->addPotionActions();
    }

    public function testConstants(): void
    {
        $this->assertEquals("lotgd2.actionGroup.healerTemplate.potions", HealerTemplate::ActionGroupPotions);
        $this->assertEquals("lotgd2.action.healerTemplate.complete", HealerTemplate::ActionCompleteHealing);
        $this->assertEquals("lotgd2.action.healerTemplate.partial", HealerTemplate::ActionPartialHealing);
    }
}