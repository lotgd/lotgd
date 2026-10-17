<?php
declare(strict_types=1);

namespace LotGD2\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PropertyHandler
{
    /**
     * @param class-string|null $name
     */
    public function __construct(
        private(set) ?string $name = null,
    ) {

    }
}