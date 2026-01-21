<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use LotGD2\Game\Random\DiceBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

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

    public function getTests()
    {
        return [
            new TwigTest("instanceof", fn(object $object, string $className) => is_a($object, $className)),
        ];
    }
}