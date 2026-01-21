<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LotGDExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter("parse", [ParseRuntime::class, "parse"]),
        ];
    }
}