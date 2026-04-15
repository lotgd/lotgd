<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\GameLoop;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class EquipmentHandler
{
    const string ArmorSlot = "armor";
    const string WeaponSlot = "weapon";
    const string PropertyName = 'equipment';

    public function __construct(
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getItemInSlot(string $slot, ?Character $character = null): ?EquipmentItem
    {
        $character ??= $this->character;
        $equipment = $character->getProperty(self::PropertyName);
        return $equipment[$slot] ?? null;
    }

    public function setItemInSlot(string $slot, EquipmentItem $item, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->logger->debug("{$character->id}: set new item in slot ($slot): {$item->getName()} ({$item->getStrength()})", [
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

    public function getName(string $slot, ?Character $character = null): string
    {
        $character ??= $this->character;
        return $this->getItemInSlot($slot, $character)?->getName() ?? $this->getEmptyName($slot);
    }

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $this->setItemInSlot(
            slot: EquipmentHandler::WeaponSlot,
            item: new EquipmentItem(
                name: $this->getEmptyName(EquipmentHandler::WeaponSlot),
                strength: 0,
                value: 0,
            ),
            character: $event->character,
        );

        $this->setItemInSlot(
            slot: EquipmentHandler::ArmorSlot,
            item: new EquipmentItem(
                name: $this->getEmptyName(EquipmentHandler::ArmorSlot),
                strength: 0,
                value: 0,
            ),
            character: $event->character
        );
    }
}