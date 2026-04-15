<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\SimpleShopTemplateType;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Scene\SceneAttachment\SimpleShopAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type SimpleShopItem array{name: string, price: int, strength: int}
 * @phpstan-type SimpleShopConfiguration array{
 *     type: "armor"|"weapon",
 *     items: SimpleShopItem[],
 *     text: array{
 *         peruse: string,
 *         itemNotFound: string,
 *         buy: string,
 *         notEnoughGold: string,
 *     },
 * }
 * @implements SceneTemplateInterface<SimpleShopConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(SimpleShopTemplateType::class)]
class SimpleShopTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const string ActionGroupShop = "lotgd.actionGroup.shop";

    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
        private readonly LoggerInterface $logger,
        private readonly ActionService $actionService,
        private readonly EquipmentHandler $equipment,
        private readonly GoldHandler $gold,
    ) {
    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameters()["op"] ?? "";
        $this->logger->debug("Called SimpleShopTemplate::onSceneChange, op={$op}");

        match ($op) {
            "peruse" => $this->peruseAction(),
            "buy" => $this->buyAction(),
            default => $this->defaultAction(),
        };
    }

    public function defaultAction(): void
    {
        $this->logger->debug("Called SimpleShopTemplate::defaultAction");

        $this->addDefaultActions();
    }

    public function peruseAction(): void
    {
        $this->logger->debug("Called SimpleShopTemplate::peruseAction");

        $slot = $this->scene->templateConfig["type"] === "armor" ? EquipmentHandler::ArmorSlot : EquipmentHandler::WeaponSlot;

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(SimpleShopAttachment::class);
        $this->logger->debug("Add SimpleShopAttachment (id={$attachment->id})");

        $buyAction = new Action($this->scene, parameters: ["op" => "buy"]);
        $this->actionService->addHiddenAction($this->stage, $buyAction);

        $oldItem = $this->equipment->getItemInSlot($slot);

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.dragonTemplate.epilogue",
                text: $this->scene->templateConfig["text"]["peruse"],
                context: [
                    "amount" => $this->getTradeInValue($oldItem?->getValue() ?? 0),
                    "item" => $oldItem?->getName() ?? "Fists",
                ]
            )
        ];

        $this->stage->addAttachment(
            $attachment, [
                "buyActionId" => $buyAction->id,
                "inventory" => $this->scene->templateConfig["items"],
            ]
        );
    }

    public function buyAction(): void
    {
        $this->logger->debug("Called SimpleShopTemplate::buyAction");

        $itemId = $this->action->getParameters()[SimpleShopAttachment::ActionParameterName] ?? -1;
        $inventory = $this->scene->templateConfig["items"];
        $item = $inventory[$itemId] ?? null;

        if (!isset($inventory[$itemId])) {
            $this->logger->debug("Buying item with number {$itemId}, but does not exist.");

            $paragraph = new Paragraph(
                id: "lotgd2.paragraph.shopTemplate.boughtItemNotFound",
                text: $this->scene->templateConfig["text"]["itemNotFound"],
            );
        } else {
            $this->logger->debug("Buying item with number {$itemId}.");

            $slot = $this->scene->templateConfig["type"] === "armor" ? EquipmentHandler::ArmorSlot : EquipmentHandler::WeaponSlot;
            $equipmentItem = new EquipmentItem(
                name: $item["name"],
                strength: $item["strength"],
                value: $item["price"],
            );

            $oldItem = $this->equipment->getItemInSlot($slot);

            if ($this->gold->getGold(null) + $this->getTradeInValue($oldItem?->getValue() ?? 0) >= $equipmentItem->getValue()) {
                $this->equipment->setItemInSlot($slot, $equipmentItem);
                $this->gold->addGold(null, -($equipmentItem->getValue() - $this->getTradeInValue($oldItem?->getValue() ?? 0)));

                $paragraph = new Paragraph(
                    id: "lotgd2.paragraph.shopTemplate.buy",
                    text: $this->scene->templateConfig["text"]["buy"],
                );
            } else {
                $paragraph = new Paragraph(
                    id: "lotgd2.paragraph.shopTemplate.boughtWithNotEnoughGold",
                    text: $this->scene->templateConfig["text"]["notEnoughGold"],
                );
            }
        }

        $paragraph->addContext("newItem", $item["name"] ?? "Unknown item");
        $this->stage->paragraphs = [
            $paragraph,
            new Paragraph(
                id: "lotgd2.paragraph.dragonTemplate.epilogue",
                text: $this->scene->templateConfig["text"]["peruse"],
                context: [
                    "amount" => $this->getTradeInValue($oldItem?->getValue() ?? 0),
                    "item" => $oldItem?->getName() ?? "Fists",
                ]
            )
        ];

        $this->addDefaultActions();
    }

    public function addDefaultActions(): void
    {
        $this->stage->addActionGroup(
            new ActionGroup(self::ActionGroupShop, $this->scene->title, -10)
        );

        $this->stage->addAction(
            self::ActionGroupShop,
            new Action($this->scene, "Browse", ["op" => "peruse"])
        );
    }

    private function getTradeInValue(int $value): int
    {
        return (int)round($value * 0.75);
    }
}