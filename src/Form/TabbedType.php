<?php
declare(strict_types=1);

namespace LotGD2\Form;

use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class TabbedType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return "tabbed";
    }
}
