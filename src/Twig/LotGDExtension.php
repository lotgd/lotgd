<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use LotGD2\Game\Random\DiceBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LotGDExtension extends AbstractExtension
{
    public function __construct(
        private readonly DiceBagInterface $diceBag,
    ) {
    }


    public function getFilters(): array
    {
        return [
            new TwigFilter("parse", [ParseRuntime::class, "parse"]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction("randomId", fn() => $this->diceBag->getRandomString(18)),
        ];
    }
}