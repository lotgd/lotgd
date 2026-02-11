<?php
declare(strict_types=1);

namespace LotGD2\Form;

/**
 * @template-covariant T of array
 */
interface TypeProvidesDefaultDataInterface
{
    /**
     * @return T
     */
    public function getDefaultData(): array;
}