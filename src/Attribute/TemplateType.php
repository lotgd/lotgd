<?php
declare(strict_types=1);

namespace LotGD2\Attribute;

use Attribute;
use InvalidArgumentException;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use Symfony\Component\Form\AbstractType;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class TemplateType
{
    /**
     * @param class-string<AbstractType<mixed>&TypeProvidesDefaultDataInterface<array<mixed>>> $type
     */
    public function __construct(
        private(set) string $type,
    ) {
        if (!is_subclass_of($this->type, TypeProvidesDefaultDataInterface::class)) {
            throw new InvalidArgumentException("Type must implement the interface ". TypeProvidesDefaultDataInterface::class);
        }

        if (!is_subclass_of($this->type, AbstractType::class)) {
            throw new InvalidArgumentException("Type must be subclass of ". AbstractType::class);
        }
    }
}