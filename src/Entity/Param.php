<?php
declare(strict_types=1);

namespace LotGD2\Entity;

class Param
{
    private mixed $value = null;

    public function asInt(): int
    {
        return (int)$this->value;
    }

    public function asString(): string
    {
        return (string)$this->value;
    }

    public function asBool(): bool
    {
        return (bool)$this->value;
    }

    public function asFloat(): float
    {
        return (float)$this->value;
    }
}