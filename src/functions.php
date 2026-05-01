<?php
declare(strict_types=1);

namespace LotGD2;

use LotGD2\Entity\Character\CharacterSpecialty;

if (!function_exists("LotGD2\array_filter_class")) {
    /**
     * @template T of object
     * @param list<mixed> $array
     * @param class-string<T> $className
     * @return list<T>
     */
    function array_filter_class(array $array, string $className): array
    {
        return array_filter($array, fn (mixed $value) => is_a($value, $className, allow_string: true));
    }
}
