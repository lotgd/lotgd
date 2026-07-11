<?php
declare(strict_types=1);

namespace LotGD2\Entity\Common;

trait PropertyTrait
{
    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    public function setProperty(string $name, mixed $value): static
    {
        dump($name, $value);
        $properties = $this->properties;
        $properties[$name] = $value;
        $this->properties = $properties;
        return $this;
    }
}