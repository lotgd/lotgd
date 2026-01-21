<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;

class Gold
{
    const string GOLD_PROPERTY = 'gold';

    public function getGold(Character $character): int
    {
        return $character->getProperty(self::GOLD_PROPERTY, 0);
    }

    public function setGold(Character $character, int $gold): static
    {
        $character->setProperty(self::GOLD_PROPERTY, $gold);
        return $this;
    }

    public function addGold(Character $character, int $gold): static
    {
        $character->setProperty(self::GOLD_PROPERTY, $this->getGold($character) + $gold);
        return $this;
    }
}