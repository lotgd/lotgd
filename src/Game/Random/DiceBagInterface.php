<?php
declare(strict_types=1);

namespace LotGD2\Game\Random;

interface DiceBagInterface
{
    /**
     * Throws one or more dices and returns the result
     * @param int $max Maximum number of eyes the dice should have. throw(6) is a standard dice with 6 sides.
     * @param int $min Minimum number of eyes the dice should have, usually 1. Can be anything.
     * @param int $times
     * @return int The result of the throw or the sum of the results if more than one throw.
     */
    public function throw(int $max, int $min = 1, int $times = 1): int;

    /**
     * Returns true if
     * @param int|float $winChance Win chance as a float between 0 and 1, or an integer between 0 and 100.
     * @param int $precision For small chances, the precision should be tunes up. For example, 0.005 would always lead to a loss unless $precision is set to 1.
     * @return bool True if won.
     */
    public function chance(int|float $winChance, int $precision = 0): bool;

    /**
     * Returns a evenly distributed number where the maximum and minum values are only half as probable.
     * @param $min
     * @param $max
     * @return int
     */
    public function pseudoBell(int $min, int $max): int;

    public function bell(float $min, float $max): float;

    public function getRandomString(
        int $length = 0,
        string $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    ): string;
}