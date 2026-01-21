<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;

class Gold
{
    const string PropertyName = 'gold';

    public function getGold(Character $character): int
    {
        return $character->getProperty(self::PropertyName, 0);
    }

    public function setGold(Character $character, int $gold): static
    {
        $character->setProperty(self::PropertyName, $gold);
        return $this;
    }

    public function addGold(Character $character, int $gold): static
    {
        $character->setProperty(self::PropertyName, $this->getGold($character) + $gold);
        return $this;
    }
}