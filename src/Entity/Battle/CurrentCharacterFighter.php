<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;

class CurrentCharacterFighter extends Fighter
{
    public static function fromCharacter(
        GameStateService $gameStateService,
        Character $character,
    ): self {
        $health = new HealthHandler($gameStateService, null, $character);
        $equipment = new EquipmentHandler(null, $character);
        $stats = new StatsHandler(null, $equipment, $character);

        $fighter =  new CurrentCharacterFighter(
            name: $character->name,
            level: $stats->getLevel(),
            weapon: $equipment->getItemInSlot(EquipmentHandler::WeaponSlot)?->getName() ?? "Fist",
            health: $health->getHealth(),
            attack: $stats->getTotalAttack(),
            defense: $stats->getTotalDefense(),
        );

        $fighter->maxHealth = $health->getMaxHealth();

        return $fighter;
    }
}