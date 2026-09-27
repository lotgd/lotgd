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

    #[TestWith([5, true])]
    #[TestWith([10, true])]
    #[TestWith([15, true])]
    #[TestWith([4, false])]
    #[TestWith([16, false])]
    #[TestWith([0, false])]
    #[TestWith([-1, false])]
    public function testIsWithin(int $value, bool $expected): void
    {
        $range = new ValueRange(5, 15);

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
