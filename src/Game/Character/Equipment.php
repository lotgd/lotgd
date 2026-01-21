<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameLoop;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class Equipment
{
    const string ArmorSlot = "armor";
    const string WeaponSlot = "weapon";
    const string PropertyName = 'equipment';

    public function __construct(
        private LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getItemInSlot(string $slot): ?EquipmentItem
    {
        $equipment = $this->character->getProperty(self::PropertyName);
        return $equipment[$slot] ?? null;
    }

    public function setItemInSlot(string $slot, EquipmentItem $item): static
    {
        $this->logger->debug("{$this->character->getId()} set new item in slot ($slot): {$item->getName()} ({$item->getStrength()})");

        $equipment = $this->character->getProperty(self::PropertyName);
        if (!is_array($equipment)) {
            $equipment[$slot] = $item;
        } else {
            $equipment = [$slot => $item];
        }
        $this->character->setProperty(self::PropertyName, $equipment);
        return $this;
    }
}