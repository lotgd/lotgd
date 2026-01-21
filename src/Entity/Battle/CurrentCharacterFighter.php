<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;

class CurrentCharacterFighter extends Fighter
{
    public static function fromCharacter(
        Character $character,
    ): self {
        $health = new Health(null, $character);
        $equipment = new Equipment(null, $character);
        $stats = new Stats(null, $equipment, $health, $character);

        $fighter =  new CurrentCharacterFighter(
            name: $character->name,
            level: $stats->getLevel(),
            weapon: $equipment->getItemInSlot(Equipment::WeaponSlot)?->getName() ?? "Fist",
            health: $health->getHealth(),
            attack: $stats->getTotalAttack(),
            defense: $stats->getTotalDefense(),
        );

        $fighter->maxHealth = $health->getMaxHealth();

        return $fighter;
    }
}