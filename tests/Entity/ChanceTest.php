<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Chance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Chance::class)]
class ChanceTest extends TestCase
{
    #[TestWith([1, 100, 2])]
    #[TestWith([1, 1000, 3])]
    #[TestWith([1, 1_000_000, 6])]
    #[TestWith([1, 1_000_000_000, 9])]
    #[TestWith([1, 2_000_000_000, 10])]
    public function testChancePrecision($numerator, $denominator, $expectedPrecision)
    {
        $chance = new Chance($numerator, $denominator);

        $this->assertSame($expectedPrecision, $chance->precision());
    }

    #[TestWith([1, 100, 0.01])]
    #[TestWith([1, 1000, 0.001])]
    #[TestWith([1, 1_000_000, 0.000_001])]
    #[TestWith([1, 1_000_000_000, 0.000_000_001])]
    #[TestWith([1, 2_000_000_000, 0.000_000_000_5])]
    public function testChanceResult($numerator, $denominator, $expectedResult)
    {

        $chance = new Chance($numerator, $denominator);

        $this->assertSame($expectedResult, $chance->chance());
    }
}
