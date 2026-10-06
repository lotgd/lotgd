<?php
declare(strict_types=1);

namespace LotGD2\Entity\DataObject;

class ValueRange
{
    public function __construct(
        public ?int $minimum = null,
        public ?int $maximum = null,
    ) {
        // Switch values if maximum is smaller than minimum
        if ($this->maximum !== null and $this->minimum !== null and $this->maximum < $this->minimum) {
            [$this->minimum, $this->maximum] = [$this->maximum, $this->minimum];
        }
    }

    public function isWithin(int $value): bool
    {
        if ($this->minimum === null and $this->maximum === null) {
            return true;
        } elseif ($this->minimum === $this->maximum and $this->minimum === $value) {
            return true;
        } elseif ($this->minimum === null) {
            return $value <= $this->maximum;
        } elseif ($this->maximum === null) {
            return $value >= $this->minimum;
        } else {
            return $value >= $this->minimum and $value <= $this->maximum;
        }
    }
}