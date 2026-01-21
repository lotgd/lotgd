<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Param;

use LotGD2\Entity\Param\ParamTypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParamTypeEnum::class)]
class ParamTypeEnumTest extends TestCase
{
    /**
     * @test
     */
    public function testIntCaseValue(): void
    {
        self::assertSame("int", ParamTypeEnum::Int->value);
    }

    /**
     * @test
     */
    public function testFloatCaseValue(): void
    {
        self::assertSame("float", ParamTypeEnum::Float->value);
    }

    /**
     * @test
     */
    public function testStringCaseValue(): void
    {
        self::assertSame("string", ParamTypeEnum::String->value);
    }

    /**
     * @test
     */
    public function testBoolCaseValue(): void
    {
        self::assertSame("bool", ParamTypeEnum::Bool->value);
    }

    /**
     * @test
     */
    public function testBagCaseValue(): void
    {
        self::assertSame("bag", ParamTypeEnum::Bag->value);
    }

    /**
     * @test
     */
    public function testAllCasesAreDefined(): void
    {
        $cases = ParamTypeEnum::cases();
        
        self::assertCount(5, $cases);
        self::assertContains(ParamTypeEnum::Int, $cases);
        self::assertContains(ParamTypeEnum::Float, $cases);
        self::assertContains(ParamTypeEnum::String, $cases);
        self::assertContains(ParamTypeEnum::Bool, $cases);
        self::assertContains(ParamTypeEnum::Bag, $cases);
    }

    /**
     * @test
     */
    public function testCanCreateFromValue(): void
    {
        self::assertSame(ParamTypeEnum::Int, ParamTypeEnum::from("int"));
        self::assertSame(ParamTypeEnum::Float, ParamTypeEnum::from("float"));
        self::assertSame(ParamTypeEnum::String, ParamTypeEnum::from("string"));
        self::assertSame(ParamTypeEnum::Bool, ParamTypeEnum::from("bool"));
        self::assertSame(ParamTypeEnum::Bag, ParamTypeEnum::from("bag"));
    }

    /**
     * @test
     */
    public function testThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        
        ParamTypeEnum::from("invalid");
    }

    /**
     * @test
     */
    public function testTryFromValidValue(): void
    {
        self::assertSame(ParamTypeEnum::Int, ParamTypeEnum::tryFrom("int"));
        self::assertNull(ParamTypeEnum::tryFrom("invalid"));
    }

    /**
     * @test
     */
    public function testEnumIsBackedByString(): void
    {
        $enum = ParamTypeEnum::Int;
        
        self::assertIsString($enum->value);
    }
}