<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use ValueError;

class EquipmentItem
{
    public function __construct(
        private string $name,
        private int $strength,
        private int $value,
    ) {
        if (strlen($name) === 0) {
            throw new ValueError("Equipment item name must not be empty.");
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}