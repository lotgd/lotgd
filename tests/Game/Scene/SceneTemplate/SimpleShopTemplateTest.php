<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\SimpleShopTemplateType;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneAttachment\SimpleShopAttachment;
use LotGD2\Game\Scene\SceneTemplate\SimpleShopTemplate;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SimpleShopTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(Attachment::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(EquipmentItem::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(SimpleShopAttachment::class)]
#[UsesClass(SimpleShopTemplateType::class)]
class SimpleShopTemplateTest extends TestCase
{
    /**
     * @return array{
     *     type: "armor"|"weapon",
     *     items: array<int, array{name: string, price: int, strength: int}>,
     *     text: array{
     *         peruse: string,
     *         itemNotFound: string,
     *         buy: string,
     *         notEnoughGold: string,
     *     },
     * }
     */
    private function getDefaultTemplateConfig(string $type = "weapon"): array
    {
        return [
            "type" => $type,
            "items" => [
                0 => [
                    "name" => "Wooden Sword",
                    "price" => 50,
                    "strength" => 2,
                ],
                1 => [
                    "name" => "Iron Sword",
                    "price" => 150,
                    "strength" => 5,
                ],
            ],
            "text" => [
                "peruse" => "You browse the shop wares.",
                "itemNotFound" => "The merchant looks at you confused.",
                "buy" => "You bought the {{ newItem }}.",
                "notEnoughGold" => "You do not have enough gold for {{ newItem }}.",
            ],
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
     * @param array<string> $mockedMethods
     * @return ($mockedMethods is non-empty-array ? SimpleShopTemplate&MockObject : SimpleShopTemplate&Stub)
     * @throws Exception
     */
    private function getPartiallyMockedSimpleShopTemplate(
        array $mockedMethods = [],
        ?AttachmentRepository $attachmentRepository = null,
        ?LoggerInterface $logger = null,
        ?ActionService $actionService = null,
        ?EquipmentHandler $equipment = null,
        ?GoldHandler $gold = null,
        ?Scene $scene = null,
        ?Stage $stage = null,
        ?Action $action = null,
        ?Character $character = null,
    ): SimpleShopTemplate {
        if (count($mockedMethods) > 0) {
            $simpleShopTemplate = $this->getMockBuilder(SimpleShopTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $actionService ?? $this->createStub(ActionService::class),
                    $equipment ?? $this->createStub(EquipmentHandler::class),
                    $gold ?? $this->createStub(GoldHandler::class),
                ])
                ->getMock();
        } else {
            $simpleShopTemplate = $this->getStubBuilder(SimpleShopTemplate::class)
                ->onlyMethods($mockedMethods)
                ->setConstructorArgs([
                    $attachmentRepository ?? $this->createStub(AttachmentRepository::class),
                    $logger ?? $this->createStub(LoggerInterface::class),
                    $actionService ?? $this->createStub(ActionService::class),
                    $equipment ?? $this->createStub(EquipmentHandler::class),
                    $gold ?? $this->createStub(GoldHandler::class),
                ])
                ->getStub();
        }

        if ($stage) {
            $simpleShopTemplate->method(PropertyHook::get("stage"))->willReturn($stage);
        }

        if ($scene) {
            $simpleShopTemplate->method(PropertyHook::get("scene"))->willReturn($scene);
        }

        if ($action) {
            $simpleShopTemplate->method(PropertyHook::get("action"))->willReturn($action);
        }

        if ($character) {
            $simpleShopTemplate->method(PropertyHook::get("character"))->willReturn($character);
        }

        return $simpleShopTemplate;
    }

    public function testConstantActionGroupShop(): void
    {
        $this->assertSame("lotgd.actionGroup.shop", SimpleShopTemplate::ActionGroupShop);
    }

    public function testOnSceneChangeDispatchesToPeruseAction(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $action->method("getParameters")->willReturn(["op" => "peruse"]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("Called SimpleShopTemplate::onSceneChange, op=peruse");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            mockedMethods: ["peruseAction"],
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->expects($this->once())->method("peruseAction");

        $template->onSceneChange();
    }

    public function testOnSceneChangeDispatchesToBuyAction(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $action->method("getParameters")->willReturn(["op" => "buy"]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("Called SimpleShopTemplate::onSceneChange, op=buy");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            mockedMethods: ["buyAction"],
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->expects($this->once())->method("buyAction");

        $template->onSceneChange();
    }

    /**
     * @param array<string, scalar> $parameters
     */
    #[TestWith([[]])]
    #[TestWith([["op" => ""]])]
    #[TestWith([["op" => "unknown"]])]
    public function testOnSceneChangeDispatchesToDefaultAction(array $parameters): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $action->method("getParameters")->willReturn($parameters);

        $op = $parameters["op"] ?? "";
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("Called SimpleShopTemplate::onSceneChange, op={$op}");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            mockedMethods: ["defaultAction"],
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->expects($this->once())->method("defaultAction");

        $template->onSceneChange();
    }

    public function testDefaultActionCallsAddDefaultActionsAndLogsDebug(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $scene->method(PropertyHook::get("title"))->willReturn("Blacksmith Shop");

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("Called SimpleShopTemplate::defaultAction");

        $stage->expects($this->once())
            ->method("addActionGroup")
            ->with($this->callback(function (ActionGroup $actionGroup) {
                $this->assertSame(SimpleShopTemplate::ActionGroupShop, $actionGroup->getId());
                $this->assertSame("Blacksmith Shop", $actionGroup->getTitle());
                $this->assertSame(-10, $actionGroup->getWeight());
                return true;
            }));

        $stage->expects($this->once())
            ->method("addAction")
            ->with(
                SimpleShopTemplate::ActionGroupShop,
                $this->callback(function (Action $addedAction) {
                    $this->assertSame("Browse", $addedAction->title);
                    $this->assertSame(["op" => "peruse"], $addedAction->getParameters());
                    return true;
                })
            );

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->defaultAction();
    }

    public function testPeruseActionWithWeaponShopAndNoOldItem(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "weapon");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $attachment = new Attachment(attachmentClass: SimpleShopAttachment::class);
        $attachment->id = 42;

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [SimpleShopAttachment::class])
            ->willReturn($attachment);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::peruseAction",
                    "Add SimpleShopAttachment (id=42)" => true,
                    default => false,
                };
            });

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())
            ->method("addHiddenAction")
            ->with($stage, $this->callback(function (Action $buyAction) {
                $this->assertSame(["op" => "buy"], $buyAction->getParameters());
                return true;
            }));

        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment->expects($this->once())
            ->method("getItemInSlot")
            ->with(EquipmentHandler::WeaponSlot)
            ->willReturn(null);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($config) {
                $this->assertCount(1, $paragraphs);
                /** @var Paragraph $paragraph */
                $paragraph = $paragraphs[0];
                $this->assertSame(SimpleShopTemplate::Paragraph["peruse"], $paragraph->id);
                $this->assertSame($config["text"]["peruse"], $paragraph->text);
                $this->assertSame(0, $paragraph->context["amount"]);
                $this->assertSame("Fists", $paragraph->context["item"]);
            });

        $stage->expects($this->once())
            ->method("addAttachment")
            ->with(
                $attachment,
                $this->callback(function (array $attachmentConfig) use ($config) {
                    $this->assertArrayHasKey("buyActionId", $attachmentConfig);
                    $this->assertSame($config["items"], $attachmentConfig["inventory"]);
                    return true;
                })
            );

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            attachmentRepository: $attachmentRepository,
            logger: $logger,
            actionService: $actionService,
            equipment: $equipment,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->peruseAction();
    }

    public function testPeruseActionWithArmorShopAndExistingOldItem(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "armor");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);

        $attachment = new Attachment(attachmentClass: SimpleShopAttachment::class);
        $attachment->id = 77;

        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method("__call")
            ->with("findOneByAttachmentClass", [SimpleShopAttachment::class])
            ->willReturn($attachment);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::peruseAction",
                    "Add SimpleShopAttachment (id=77)" => true,
                    default => false,
                };
            });

        $actionService = $this->createMock(ActionService::class);
        $actionService->expects($this->once())
            ->method("addHiddenAction")
            ->with($stage, $this->isInstanceOf(Action::class));

        $oldItem = new EquipmentItem(name: "Leather Armor", strength: 3, value: 80);
        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment->expects($this->once())
            ->method("getItemInSlot")
            ->with(EquipmentHandler::ArmorSlot)
            ->willReturn($oldItem);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($config) {
                $this->assertCount(1, $paragraphs);
                /** @var Paragraph $paragraph */
                $paragraph = $paragraphs[0];
                $this->assertSame(SimpleShopTemplate::Paragraph["peruse"], $paragraph->id);
                $this->assertSame($config["text"]["peruse"], $paragraph->text);
                $this->assertSame(60, $paragraph->context["amount"]); // round(80 * 0.75) = 60
                $this->assertSame("Leather Armor", $paragraph->context["item"]);
            });

        $stage->expects($this->once())
            ->method("addAttachment")
            ->with(
                $attachment,
                $this->callback(function (array $attachmentConfig) use ($config) {
                    $this->assertSame($config["items"], $attachmentConfig["inventory"]);
                    return true;
                })
            );

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            attachmentRepository: $attachmentRepository,
            logger: $logger,
            actionService: $actionService,
            equipment: $equipment,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->peruseAction();
    }

    public function testBuyActionWhenItemNotFoundWithInvalidIndex(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "weapon");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);
        $scene->method(PropertyHook::get("title"))->willReturn("Shop");

        $action->method("getParameters")->willReturn([
            SimpleShopAttachment::ActionParameterName => 5,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::buyAction",
                    "Buying item with number 5, but does not exist." => true,
                    default => false,
                };
            });

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($config) {
                $this->assertCount(2, $paragraphs);
                $this->assertSame("lotgd2.paragraph.shopTemplate.boughtItemNotFound", $paragraphs[0]->id);
                $this->assertSame($config["text"]["itemNotFound"], $paragraphs[0]->text);
                $this->assertSame("Unknown item", $paragraphs[0]->context["newItem"]);

                $this->assertSame(SimpleShopTemplate::Paragraph["peruse"], $paragraphs[1]->id);
                $this->assertSame($config["text"]["peruse"], $paragraphs[1]->text);
                $this->assertSame(0, $paragraphs[1]->context["amount"]);
                $this->assertSame("Fists", $paragraphs[1]->context["item"]);
            });

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }

    public function testBuyActionWhenItemNotFoundWithMissingActionParameter(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "weapon");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);
        $scene->method(PropertyHook::get("title"))->willReturn("Shop");

        $action->method("getParameters")->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::buyAction",
                    "Buying item with number -1, but does not exist." => true,
                    default => false,
                };
            });

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) {
                $this->assertCount(2, $paragraphs);
                $this->assertSame("lotgd2.paragraph.shopTemplate.boughtItemNotFound", $paragraphs[0]->id);
                $this->assertSame("Unknown item", $paragraphs[0]->context["newItem"]);
            });

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }

    public function testBuyActionWhenItemFoundAndEnoughGoldForWeaponWithNoOldItem(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "weapon");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);
        $scene->method(PropertyHook::get("title"))->willReturn("Weapon Shop");

        $action->method("getParameters")->willReturn([
            SimpleShopAttachment::ActionParameterName => 1,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::buyAction",
                    "Buying item with number 1." => true,
                    default => false,
                };
            });

        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment->expects($this->once())
            ->method("getItemInSlot")
            ->with(EquipmentHandler::WeaponSlot)
            ->willReturn(null);

        $equipment->expects($this->once())
            ->method("setItemInSlot")
            ->with(
                EquipmentHandler::WeaponSlot,
                $this->callback(function (EquipmentItem $item) {
                    $this->assertSame("Iron Sword", $item->getName());
                    $this->assertSame(5, $item->getStrength());
                    $this->assertSame(150, $item->getValue());
                    return true;
                })
            );

        $gold = $this->createMock(GoldHandler::class);
        $gold->expects($this->once())
            ->method("getGold")
            ->with(null)
            ->willReturn(200);

        $gold->expects($this->once())
            ->method("addGold")
            ->with(null, -150);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($config) {
                $this->assertCount(2, $paragraphs);
                $this->assertSame(SimpleShopTemplate::Paragraph["buy"], $paragraphs[0]->id);
                $this->assertSame($config["text"]["buy"], $paragraphs[0]->text);
                $this->assertSame("Iron Sword", $paragraphs[0]->context["newItem"]);

                $this->assertSame(SimpleShopTemplate::Paragraph["peruse"], $paragraphs[1]->id);
                $this->assertSame(0, $paragraphs[1]->context["amount"]);
                $this->assertSame("Fists", $paragraphs[1]->context["item"]);
            });

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            equipment: $equipment,
            gold: $gold,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }

    public function testBuyActionWhenItemFoundAndEnoughGoldForArmorWithOldItemTradeIn(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "armor");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);
        $scene->method(PropertyHook::get("title"))->willReturn("Armor Shop");

        $action->method("getParameters")->willReturn([
            SimpleShopAttachment::ActionParameterName => 0,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::buyAction",
                    "Buying item with number 0." => true,
                    default => false,
                };
            });

        $oldItem = new EquipmentItem(name: "Cloth Armor", strength: 1, value: 40);
        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment->expects($this->once())
            ->method("getItemInSlot")
            ->with(EquipmentHandler::ArmorSlot)
            ->willReturn($oldItem);

        $equipment->expects($this->once())
            ->method("setItemInSlot")
            ->with(
                EquipmentHandler::ArmorSlot,
                $this->callback(function (EquipmentItem $item) {
                    $this->assertSame("Wooden Sword", $item->getName());
                    $this->assertSame(2, $item->getStrength());
                    $this->assertSame(50, $item->getValue());
                    return true;
                })
            );

        // Trade-in is round(40 * 0.75) = 30. Item price is 50. Character gold is 25. 25 + 30 = 55 >= 50.
        // Gold to deduct: -(50 - 30) = -20.
        $gold = $this->createMock(GoldHandler::class);
        $gold->expects($this->once())
            ->method("getGold")
            ->with(null)
            ->willReturn(25);

        $gold->expects($this->once())
            ->method("addGold")
            ->with(null, -20);

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) {
                $this->assertCount(2, $paragraphs);
                $this->assertSame(SimpleShopTemplate::Paragraph["buy"], $paragraphs[0]->id);
                $this->assertSame("Wooden Sword", $paragraphs[0]->context["newItem"]);

                $this->assertSame(SimpleShopTemplate::Paragraph["peruse"], $paragraphs[1]->id);
                $this->assertSame(30, $paragraphs[1]->context["amount"]);
                $this->assertSame("Cloth Armor", $paragraphs[1]->context["item"]);
            });

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            equipment: $equipment,
            gold: $gold,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }

    public function testBuyActionWhenItemFoundButNotEnoughGold(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $config = $this->getDefaultTemplateConfig(type: "weapon");
        $scene->method(PropertyHook::get("templateConfig"))->willReturn($config);
        $scene->method(PropertyHook::get("title"))->willReturn("Weapon Shop");

        $action->method("getParameters")->willReturn([
            SimpleShopAttachment::ActionParameterName => 0,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method("debug")
            ->willReturnCallback(function (string $message) {
                return match ($message) {
                    "Called SimpleShopTemplate::buyAction",
                    "Buying item with number 0." => true,
                    default => false,
                };
            });

        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment->expects($this->once())
            ->method("getItemInSlot")
            ->with(EquipmentHandler::WeaponSlot)
            ->willReturn(null);

        $equipment->expects($this->never())->method("setItemInSlot");

        $gold = $this->createMock(GoldHandler::class);
        $gold->expects($this->once())
            ->method("getGold")
            ->with(null)
            ->willReturn(10); // 10 + 0 < 50

        $gold->expects($this->never())->method("addGold");

        $stage->expects($this->once())
            ->method(PropertyHook::set("paragraphs"))
            ->willReturnCallback(function (array $paragraphs) use ($config) {
                $this->assertCount(2, $paragraphs);
                $this->assertSame("lotgd2.paragraph.shopTemplate.boughtWithNotEnoughGold", $paragraphs[0]->id);
                $this->assertSame($config["text"]["notEnoughGold"], $paragraphs[0]->text);
                $this->assertSame("Wooden Sword", $paragraphs[0]->context["newItem"]);
            });

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            logger: $logger,
            equipment: $equipment,
            gold: $gold,
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }

    public function testDefaultActionWithEmptyConfiguration(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials(
            stageAsMock: true,
        );

        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);
        $scene->method(PropertyHook::get("title"))->willReturn("Empty Shop");

        $stage->expects($this->once())->method("addActionGroup");
        $stage->expects($this->once())->method("addAction");

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->defaultAction();
    }

    #[DoesNotPerformAssertions]
    public function testPeruseActionWithEmptyConfiguration(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->peruseAction();
    }

    #[DoesNotPerformAssertions]
    public function testBuyActionWithEmptyConfiguration(): void
    {
        [$character, $scene, $stage, $action] = $this->getTemplateEssentials();
        $scene->method(PropertyHook::get("templateConfig"))->willReturn([]);

        $template = $this->getPartiallyMockedSimpleShopTemplate(
            scene: $scene,
            stage: $stage,
            action: $action,
            character: $character,
        );

        $template->buyAction();
    }
}
