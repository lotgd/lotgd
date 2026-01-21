<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use LotGD2\Game\Random\DiceBag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class LotGDExtension extends AbstractExtension
{
    public function __construct(
        private readonly DiceBag $diceBag,
    ) {
    }


    public function getFilters(): array
    {
        return [
            new TwigFilter("parse", [ParseRuntime::class, "parse"]),
            new TwigFilter("stringify", function(mixed $value) {
                if ($value instanceof \Stringable) {
                    return $value->__toString();
                } elseif (is_object($value)) {
                    return get_class($value);
                } else {
                    return (string) $value;
                }
            })
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