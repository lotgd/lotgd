<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;

class CurrentCharacterFighter extends Fighter
{
    public static function fromCharacter(
        GameStateService $gameStateService,
        HandlerRegistry $handlerRegistry,
        Character $character,
    ): self {
        $health = $handlerRegistry->get(HealthHandler::class);
        $stats = $handlerRegistry->get(StatsHandler::class);
        $equipment = $handlerRegistry->get(EquipmentHandler::class);

        $fighter =  new CurrentCharacterFighter(
            name: $character->name,
            level: $stats->getLevel($character),
            weapon: $equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot)?->getName() ?? "Fist",
            health: $health->getHealth($character),
            attack: $stats->getTotalAttack($character),
            defense: $stats->getTotalDefense($character),
        );

        $fighter->maxHealth = $health->getMaxHealth($character);

        return $fighter;
    }
}