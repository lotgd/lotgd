<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\DataObject;

use LotGD2\Entity\DataObject\ValueRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValueRange::class)]
class ValueRangeTest extends TestCase
{
    public function testConstructorPreservesOrderedMinAndMax(): void
    {
        $range = new ValueRange(5, 15);

        $this->assertSame(5, $range->minimum);
        $this->assertSame(15, $range->maximum);
    }

    public function testConstructorSwapsMinAndMaxWhenMaxIsSmallerThanMin(): void
    {
        $range = new ValueRange(20, 10);

        $this->assertSame(10, $range->minimum);
        $this->assertSame(20, $range->maximum);
    }

    public function testConstructorWithEqualMinAndMax(): void
    {
        $range = new ValueRange(7, 7);

        $this->assertSame(7, $range->minimum);
        $this->assertSame(7, $range->maximum);
    }

    public function testConstructorWithNegativeValuesSwapsCorrectly(): void
    {
        $range = new ValueRange(-5, -15);

        $this->assertSame(-15, $range->minimum);
        $this->assertSame(-5, $range->maximum);
    }

    #[TestWith([5, 15, 5, true], name: "5 in [5, 15] is true")]
    #[TestWith([5, 15, 10, true], name: "10 in [5, 15] is true")]
    #[TestWith([5, 15, 15, true], name: "15 in [5, 15] is true")]
    #[TestWith([5, 15, 4, false], name: "4 in [5, 15] is false")]
    #[TestWith([5, 15, 16, false], name: "16 in [5, 15] is false")]
    #[TestWith([5, 15, 0, false], name: "0 in [5, 15] is false")]
    #[TestWith([5, 15, -1, false], name: "-1 in [5, 15] is false")]
    #[TestWith([null, null, 0, true], name: "0 in [null, null] is true")]
    #[TestWith([null, null, 1, true], name: "1 in [null, null] is true")]
    #[TestWith([null, null, -1, true], name: "-1 in [null, null] is true")]
    #[TestWith([null, null, PHP_INT_MAX, true], name: "INT_MAX in [null, null] is true")]
    #[TestWith([null, null, PHP_INT_MIN, true], name: "INT_MIN in [null, null] is true")]
    #[TestWith([null, 0, 0, true], name: "0 in [null, 0] is true")]
    #[TestWith([null, 0, 1, false], name: "1 in [null, 0] is false")]
    #[TestWith([null, 0, -1, true], name: "-1 in [null, 0] is true")]
    #[TestWith([null, 0, PHP_INT_MAX, false], name: "INT_MAX in [null, 0] is false")]
    #[TestWith([null, 0, PHP_INT_MIN, true], name: "INT_MIN in [null, 0] is true")]
    #[TestWith([0, null, 0, true], name: "0 in [0, null] is true")]
    #[TestWith([0, null, -1, false], name: "-1 in [0, null] is false")]
    #[TestWith([0, null, 1, true], name: "1 in [0, null] is true")]
    #[TestWith([0, null, PHP_INT_MAX, true], name: "INT_MAX in [0, null] is true")]
    #[TestWith([0, null, PHP_INT_MIN, false], name: "INT_MIN in [0, null] is false")]
    #[TestWith([0, 0, 0, true], name: "0 in [0, 0]")]
    #[TestWith([PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, true], name: "INT_MAX in [INT_MAX, INT_MAX]")]
    #[TestWith([PHP_INT_MIN, PHP_INT_MIN, PHP_INT_MIN, true], name: "PHP_INT_MIN in [PHP_INT_MIN, PHP_INT_MIN]")]
    public function testIsWithin(
        ?int $min,
        ?int $max,
        int $value,
        bool $expected
    ): void {
        $range = new ValueRange($min, $max);

        $this->assertSame($expected, $range->isWithin($value));
    }

    public function testIsWithinWithEqualMinAndMax(): void
    {
        $range = new ValueRange(10, 10);

        $this->assertTrue($range->isWithin(10));
        $this->assertFalse($range->isWithin(9));
        $this->assertFalse($range->isWithin(11));
    }
}
