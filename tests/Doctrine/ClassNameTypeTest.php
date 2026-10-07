<?php
declare(strict_types=1);

namespace LotGD2\Tests\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use LotGD2\Doctrine\ClassNameType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassNameType::class)]
class ClassNameTypeTest extends TestCase
{
    public function testClassNameReturnsClassNameConstant()
    {
        $className = new ClassNameType();
        $this->assertSame(ClassNameType::NAME, $className->getName());
    }

    public function testClassNameIsWhatWasExpected()
    {
        $className = new ClassNameType();
        $this->assertSame("class_name", $className->getName());
    }

    #[TestWith([null, null])]
    #[TestWith([self::class, self::class])]
    #[TestWith(['LotGD2\Class\Does\Not\Exist', null])]
    public function testIfTypeValueIsConvertedCorrectly(
        ?string $inputValue,
        ?string $expectedValue,
    ) {
        $className = new ClassNameType();
        $platform = $this->createStub(AbstractPlatform::class);

        $this->assertSame($expectedValue, $className->convertToPHPValue($inputValue, $platform));
    }
}
