<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use Psr\Log\LoggerInterface;

class Equipment
{
    const string ArmorSlot = "armor";
    const string WeaponSlot = "weapon";

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getItemInSlot(Character $character, string $slot): ?EquipmentItem
    {
        $equipment = $character->getProperty("equipment");
        return $equipment[$slot] ?? null;
    }

    public function setItemInSlot(Character $character, string $slot, EquipmentItem $item): static
    {
        $this->logger->debug("{$character->getId()} set new item in slot ($slot): {$item->getName()} ({$item->getStrength()})");

        $equipment = $character->getProperty("equipment");
        if (!is_array($equipment)) {
            $equipment[$slot] = $item;
        } else {
            $equipment = [$slot => $item];
        }
        $character->setProperty("equipment", $equipment);
        return $this;
    }
}