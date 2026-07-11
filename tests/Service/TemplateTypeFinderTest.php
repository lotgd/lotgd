<?php
declare(strict_types=1);

namespace LotGD2\Tests\Service;

use LotGD2\Attribute\TemplateType;
use LotGD2\Service\TemplateTypeFinder;
use LotGD2\Tests\Fixtures\EmptyTestClass;
use LotGD2\Tests\Fixtures\TemplateTypeTestClassInvalid;
use LotGD2\Tests\Fixtures\TemplateTypeTestClassValid;
use LotGD2\Tests\Fixtures\TestType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueError;

#[CoversClass(TemplateTypeFinder::class)]
#[UsesClass(TemplateType::class)]
class TemplateTypeFinderTest extends TestCase
{
    public function testFindWithEmptyString()
    {
        $finder = new TemplateTypeFinder(
            $this->createStub(LoggerInterface::class),
        );

        $result = $finder->find("");
        $this->assertNull($result);
    }

    public function testFindWithNonExistingClassString()
    {
        $finder = new TemplateTypeFinder(
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Template class 'AClassThatDoesNotExist' does not exist");

        $finder->find("AClassThatDoesNotExist");
    }

    public function testIfFindReturnsNullIfAttributeWasNotAdded()
    {
        $finder = new TemplateTypeFinder(
            $this->createStub(LoggerInterface::class),
        );

        $result = $finder->find(EmptyTestClass::class);
        $this->assertNull($result);
    }

    public function testIfFindReturnsNullIfAttributeWasAddedButIsNotAbstractType()
    {
        $finder = new TemplateTypeFinder(
            $this->createStub(LoggerInterface::class),
        );

        $result = $finder->find(TemplateTypeTestClassInvalid::class);
        $this->assertNull($result);
    }

    public function testIfFindReturnsNullIfAttributeWasAdded()
    {
        $finder = new TemplateTypeFinder(
            $this->createStub(LoggerInterface::class),
        );

        $result = $finder->find(TemplateTypeTestClassValid::class);

        $this->assertSame(TestType::class, $result);
    }
}
