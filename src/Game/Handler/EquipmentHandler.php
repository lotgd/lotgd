<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Attribute\PropertyHandler;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[PropertyHandler]
readonly class EquipmentHandler
{
    const string ArmorSlot = "armor";
    const string WeaponSlot = "weapon";
    const string PropertyName = 'equipment';

    public function __construct(
        private ?LoggerInterface $logger,
    ) {
    }

    public function getItemInSlot(Character $character, string $slot): ?EquipmentItem
    {
        $equipment = $character->getProperty(self::PropertyName);
        return $equipment[$slot] ?? null;
    }

    public function setItemInSlot(Character $character, string $slot, EquipmentItem $item): static
    {
        $this->logger?->debug("{$character->id}: set new item in slot ($slot): {$item->getName()} ({$item->getStrength()})", [
            "item" => $item,
        ]);

        $equipment = $character->getProperty(self::PropertyName);
        if (is_array($equipment)) {
            $equipment[$slot] = $item;
        } else {
            $equipment = [$slot => $item];
        }
        $character->setProperty(self::PropertyName, $equipment);
        return $this;
    }

    public function getEmptyName(string $slot): string
    {
        return match ($slot) {
            EquipmentHandler::WeaponSlot => "Fists",
            EquipmentHandler::ArmorSlot => "T-Shirt",
            default => "Nothing",
        };
    }

    public function getName(Character $character, string $slot): string
    {
        return $this->getItemInSlot($character, $slot)?->getName() ?? $this->getEmptyName($slot);
    }

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $this->setItemInSlot(
            character: $event->character,
            slot: EquipmentHandler::WeaponSlot,
            item: new EquipmentItem(
                name: $this->getEmptyName(EquipmentHandler::WeaponSlot),
                strength: 0,
                value: 0,
            ),
        );

        $this->setItemInSlot(
            character: $event->character,
            slot: EquipmentHandler::ArmorSlot,
            item: new EquipmentItem(
                name: $this->getEmptyName(EquipmentHandler::ArmorSlot),
                strength: 0,
                value: 0,
            ),
        );
    }
}