<?php
declare(strict_types=1);

namespace LotGD2\Tests\Attribute;

use InvalidArgumentException;
use LotGD2\Attribute\TemplateType;
use LotGD2\Tests\Fixtures\TemplateTypeTestClassInvalid;
use LotGD2\Tests\Fixtures\TemplateTypeTestClassValid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(TemplateType::class)]
class TemplateTypeTest extends TestCase
{
    public function testThatTemplateTypeThrowsAnExceptionIfClassItNotOfAbstractType()
    {
        $reflection = new ReflectionClass(TemplateTypeTestClassInvalid::class);
        $attributes = $reflection->getAttributes(TemplateType::class);

        $this->expectException(InvalidArgumentException::class);
        $attribute = $attributes[0]->newInstance();
    }

    public function testThatTemplateTypeCanBeUsedIfArgumentIsAnException()
    {
        $reflection = new ReflectionClass(TemplateTypeTestClassValid::class);
        $attributes = $reflection->getAttributes(TemplateType::class);

        $attribute = $attributes[0]->newInstance();

        $this->assertInstanceOf(TemplateType::class, $attribute);
    }
}
