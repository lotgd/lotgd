<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Random;

use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(DiceBag::class)]
class DiceBagTest extends TestCase
{
    public function testIfSeedIsReturned(): void
    {
        $diceBag = new DiceBag(1337);
        $this->assertSame(1337, $diceBag->getSeed());
    }

    public function testIfThrowThrowsExceptionWhenTimesLessThanOne(): void
    {
        $diceBag = new DiceBag(1337);

        $this->expectException(ValueError::class);
        $diceBag->throw(6, times: 0);
    }

    public function testIfDiceBagReturnsExpectedNumbers(): void
    {
        $diceBag = new DiceBag(1337);

        $this->assertSame(6, $diceBag->throw(6, 6, 1));
        $this->assertSame(12, $diceBag->throw(6, 6, 2));

        $this->assertSame(2, $diceBag->throw(1, 6, 1));
        $this->assertSame(2, $diceBag->throw(1, 6, 1));
        $this->assertSame(3, $diceBag->throw(1, 6, 1));
        $this->assertSame(5, $diceBag->throw(1, 6, 1));
        $this->assertSame(6, $diceBag->throw(1, 6, 1));

        $this->assertSame(28, $diceBag->throw(1, 6, 10));
    }

    public function testIfChanceThrowsValueErrorWhenPrecisionIsBelow0(): void
    {
        $diceBag = new DiceBag(1337);

        $this->expectException(ValueError::class);
        $diceBag->chance(50, precision: -1);
    }

    public function testIfChanceAcceptsIntegerAsArgument(): void
    {
        $diceBag = new DiceBag(1337);
        $this->assertTrue($diceBag->chance(100));
        $this->assertFalse($diceBag->chance(0));

        $this->assertTrue($diceBag->chance(50));
        $this->assertFalse($diceBag->chance(50));
        $this->assertFalse($diceBag->chance(50));
    }
    public function testIfChanceAcceptsFloatsAsArgument(): void
    {
        $diceBag = new DiceBag(1337);
        $this->assertTrue($diceBag->chance(1.));
        $this->assertFalse($diceBag->chance(0));

        $this->assertFalse($diceBag->chance(0));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertTrue($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
        $this->assertFalse($diceBag->chance(0.1));
    }

    public function testIfPseudoBellReturnsInputMinEqualsMax(): void
    {
        $diceBag = new DiceBag(1337);

        $this->assertSame(10, $diceBag->pseudoBell(10, 10));
    }

    public function testIfPseusoBellReturnsExpectedNumbers(): void
    {
        $diceBag = new DiceBag(1337);

        $this->assertSame(1, $diceBag->pseudoBell(2));
        $this->assertSame(3, $diceBag->pseudoBell(1, 3));
        $this->assertSame(3, $diceBag->pseudoBell(3, 1));
    }

    public function testIfBellReturnsInputMinEqualsMax(): void
    {
        $diceBag = new DiceBag(1337);

        $this->assertSame(10., $diceBag->bell(10, 10));
    }
    public function testIfBellReturnsExpectedNumbers(): void
    {
        $diceBag = new DiceBag(1337);

        $this->assertEqualsWithDelta(1.66268, $diceBag->bell(1, 3), 1e-5);
        $this->assertEqualsWithDelta(4.52334, $diceBag->bell(6, 3), 1e-5);
    }

    public function testIfGetRandomStringsThrowsExceptionIfEmptyStringIsRequested(): void
    {
        $diceBag = new DiceBag(1337);
        $this->expectException(ValueError::class);

        $diceBag->getRandomString(0);
    }

    public function testIfGetRandomStringsThrowsExceptionIfAlphabetEmpty(): void
    {
        $diceBag = new DiceBag(1337);
        $this->expectException(ValueError::class);

        $diceBag->getRandomString(20, "");
    }

    public static function randomStringProvider(): array
    {
        return [
            [1337, [5, "A"], "AAAAA"],
            [1337, [5, "ABC"], "AABAC"],
            [1989, [5, "ABC"], "CAABB"],
            [1337, [10], "xmud9GpCdF"],
        ];
    }

    #[DataProvider("randomStringProvider")]
    public function testIfRandomStringReturnsExpectedValues(int $seed, array $arguments, string $result): void
    {
        $diceBag = new DiceBag($seed);

        $this->assertSame($result, $diceBag->getRandomString(... $arguments));
    }
}
