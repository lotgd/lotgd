<?php
declare(strict_types=1);

namespace LotGD2\Entity\DataObject;

class ValueRange
{
    public function __construct(
        public ?int $minimum,
        public ?int $maximum,
    ) {
        // Switch values if maximum is smaller than minimum
        if ($this->maximum < $this->minimum) {
            [$this->minimum, $this->maximum] = [$this->maximum, $this->minimum];
        }
    }

    public function isWithin(int $value): bool
    {
        return $value >= $this->minimum and $value <= $this->maximum;
    }
}