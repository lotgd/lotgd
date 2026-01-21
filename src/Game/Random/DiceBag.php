<?php
declare(strict_types=1);

namespace LotGD2\Game\Random;

use Random\Engine;
use Random\Engine\Mt19937;
use Random\Randomizer;

readonly class DiceBag implements DiceBagInterface
{
    private Engine $engine;
    private Randomizer $randomizer;

    public function __construct(
        private int $seed = 1337,
    ) {
        $this->engine = new Mt19937();
        $this->randomizer = new Randomizer($this->engine);
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    /**
     * @param int $max
     * @param int $min
     * @param int $times, must be at least 1
     * @return int The value of the dice or the sum of multiple throws
     */
    public function throw(int $max, int $min = 1, int $times = 1): int
    {
        if ($times < 1) {
            throw new \ValueError('The argument $times must be at least 1.');
        }

        if ($min === $max) {
            // A die with min and max being equal is boring, but if you want that ..
            return $min * $times;
        } elseif ($min > $max) {
            // If minimum is larger than maximum, we the variables.
            [$min, $max] = [$max, $min];
        }

        $result = 0;

        do {
            $result += $this->randomizer->getInt($min, $max);
            $times--;
        } while ($times > 1);

        return $result;
    }

    public function chance(int|float $winChance, $precision = 0): bool
    {
        if ($precision < 0) {
            throw new \ValueError('The argument $precision must be at least 0.');
        }

        // Convert percentage unit to float
        if (is_int($winChance)) {
            $winChance /= 100;
        }

        $minNumber = 0;
        $scale = 10**($precision + 2);
        $maxNumber = $scale - 1;

        // Get a random number and divide by the scale. If smaller than $winChance, we evaluate it as a win
        // Reason: If precision is 0, scale is 100 and maxNumber is 99.
        //         If winChance is 20 (or 0.2), then there will be 20 results below 20 (0...19), making that 20%
        //         If winChance is 100, then this will always be a win.
        //         If winChance is smaller than 0, it will always be a loss.
        if ($winChance >= 1) {
            return true;
        } elseif ($winChance < 0) {
            return false;
        } elseif ($this->randomizer->getInt($minNumber, $maxNumber)/$scale < $winChance) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * An implementation of a "pseudo-bell" random number generator where the two extremes are more rare
     * Originally used in lotgd v0.9.7
     * @author MighyE, JT
     * @param int $min
     * @param int $max
     * @return int
     */
    public function pseudoBell(int $min = 0, int $max = 0): int
    {
        if ($min === $max) {
            return $min;
        } elseif ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $min *= 1000;
        $max *= 1000;

        return (int)round($this->randomizer->getInt($min, $max)/1000);
    }

    public function bell(float $min, float $max): float
    {
        if ($min === $max) {
            return $min;
        } elseif ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $mean = ($max - $min) / 2;

        do {
            $randomNumber = $mean + $this->nextGaussian() * 3.5 * ($min + $max);
        } while ($randomNumber < $min or $randomNumber > $max);

        return $randomNumber;
    }

    /**
     * Creates a new gaussian according to the Marsaglia polar method
     * @return float
     */
    private function nextGaussian(): float {
        do {
            $v1 = 2 * $this->randomizer->nextFloat() - 1;
            $v2 = 2 * $this->randomizer->nextFloat() - 1;

            $s = $v1**2 + $v2**2;
        } while ($s >= 1 or $s <= 0);

        $s = sqrt(-2 * log($s) / $s);
        return $v1 * $s;
    }

    public function getRandomString(
        int $length = 0,
        string $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    ): string {
        if ($length <= 0) {
            throw new \ValueError("Lenght must be at least 1.");
        }

        $alphabetSize = strlen($alphabet);
        if ($alphabetSize <= 0) {
            throw new \ValueError("Alphabet cannot be an empty string");
        }

        $randomString = "";
        for ($i = 0; $i < $length; $i++) {
            $randomInt = $this->randomizer->getInt(0, $alphabetSize-1);
            $randomString .= $alphabet[$randomInt];
        }

        return $randomString;
    }
}