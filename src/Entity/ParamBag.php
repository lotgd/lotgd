<?php
declare(strict_types=1);

namespace LotGD2\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class ParamBag
{
    /** @var ArrayCollection  */
    private ArrayCollection $params;

    public function __construct()
    {
        $this->params = new ArrayCollection();
    }

    public function set(string $name, mixed $value): void
    {
        if ($value instanceof Param === false) {
            $value = new Param($value);
        }

        $this->params->set($name, $value);
    }

    public function get(string $name, mixed $default = null): Param
    {
        return $this->params->get($name) ?? new Param($default);
    }
}