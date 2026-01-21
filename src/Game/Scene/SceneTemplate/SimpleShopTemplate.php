<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Scene\SceneAttachment\SimpleShopAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Autoconfigure(public: true)]
readonly class SimpleShopTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const string ActionGroupShop = "lotgd.actionGroup.shop";

    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private LoggerInterface $logger,
        private ActionService $actionService,
        private Equipment $equipment,
        private Gold $gold,
    ) {
    }

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->define("type")
            ->required()
            ->allowedTypes("string")
            ->allowedValues("armor", "weapon")
        ;

        $resolver->define("items")
            ->allowedTypes("array[]")
            ->allowedValues(function (array &$elements): bool {
                $subResolver = new OptionsResolver();

                $subResolver->define('name')
                    ->required()
                    ->allowedTypes('string');

                $subResolver->define('price')
                    ->required()
                    ->allowedTypes('int');

                $subResolver->define('strength')
                    ->required()
                    ->allowedTypes('int');

                // Trick is here: use array_map to resolve each elements one by one.
                $elements = array_map([$subResolver, 'resolve'], $elements);
                return true;
            })
            ->required();

        $resolver->define("text")
            ->default(function (OptionsResolver $resolver) {
                 $resolver
                     ->define("peruse")
                     ->required()
                     ->allowedTypes('string');

                $resolver
                    ->define("itemNotFound")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("buy")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("notEnoughGold")
                    ->required()
                    ->allowedTypes('string');
            });

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameters()["op"] ?? "";
        $this->logger->debug("Called SimpleShopTemplate::onSceneChange, op={$op}");

        match ($op) {
            "peruse" => $this->peruseAction($stage, $action, $scene),
            "buy" => $this->buyAction($stage, $action, $scene),
            default => $this->defaultAction($stage, $action, $scene),
        };
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called SimpleShopTemplate::defaultAction");

        $this->addDefaultActions($stage, $scene);
    }

    public function peruseAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called SimpleShopTemplate::peruseAction");

        $character = $stage->getOwner();
        $slot = $scene->getTemplateConfig()["type"] === "armor" ? Equipment::ArmorSlot : Equipment::WeaponSlot;

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(SimpleShopAttachment::class);
        $this->logger->debug("Add SimpleShopAttachment (id={$attachment->getId()})");

        $buyAction = new Action($scene, parameters: ["op" => "buy"]);
        $this->actionService->addHiddenAction($stage, $buyAction);

        $oldItem = $this->equipment->getItemInSlot($slot);

        $stage
            ->setDescription($scene->getTemplateConfig()["text"]["peruse"])
            ->setContext([
                "amount" => $this->getTradeInValue($oldItem?->getValue() ?? 0),
                "item" => $oldItem?->getName() ?? "Fists",
            ])
            ->addAttachment(
                $attachment, [
                    "buyActionId" => $buyAction->id,
                    "inventory" => $scene->getTemplateConfig()["items"],
                ]
            )
        ;
    }

    public function buyAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called SimpleShopTemplate::buyAction");

        $itemId = $action->getParameters()[SimpleShopAttachment::ActionParameterName] ?? -1;
        $inventory = $scene->getTemplateConfig()["items"];
        $item = $inventory[$itemId] ?? null;
        $character = $stage->getOwner();

        if (!isset($inventory[$itemId])) {
            $this->logger->debug("Buying item with number {$itemId}, but does not exist.");

            $description = $scene->getTemplateConfig()["text"]["itemNotFound"];
        } else {
            $this->logger->debug("Buying item with number {$itemId}.");

            $slot = $scene->getTemplateConfig()["type"] === "armor" ? Equipment::ArmorSlot : Equipment::WeaponSlot;
            $description = $scene->getTemplateConfig()["text"]["buy"];
            $equipmentItem = new EquipmentItem(
                name: $item["name"],
                strength: $item["strength"],
                value: $item["price"],
            );

            if ($this->gold->getGold() >= $equipmentItem->getValue()) {
                $oldItem = $this->equipment->getItemInSlot($slot);

                $this->equipment->setItemInSlot($slot, $equipmentItem);
                $this->gold->addGold(-($equipmentItem->getValue() - $this->getTradeInValue($oldItem?->getValue() ?? 0)));
            } else {
                $description = $scene->getTemplateConfig()["text"]["notEnoughGold"];
            }
        }

        $stage->setDescription($description);
        $stage->setContext([
            "newitem" => $item["name"] ?? "Unknown item",
        ]);
        $this->addDefaultActions($stage, $scene);
    }

    public function addDefaultActions(Stage $stage, Scene $scene): void
    {
        $stage->addActionGroup(
            new ActionGroup(self::ActionGroupShop, $scene->getTitle(), -10)
        );

        $stage->addAction(
            self::ActionGroupShop,
            new Action($scene, "Browse", ["op" => "peruse"])
        );
    }

    private function getTradeInValue(int $value): int
    {
        return (int)round($value * 0.75);
    }
}