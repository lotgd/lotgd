<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Param;

use LotGD2\Entity\Param\Param;
use LotGD2\Entity\Param\ParamBag;
use LotGD2\Entity\Param\ParamTypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Param::class)]
#[UsesClass(ParamBag::class)]
class ParamTest extends TestCase
{
    public function testConstructorWithInt(): void
    {
        $param = new Param(42);

        $this->assertSame(42, $param->getValue());
        $this->assertSame(ParamTypeEnum::Int, $this->getParamType($param));
    }

    public function testConstructorWithFloat(): void
    {
        $param = new Param(3.14);
        
        $this->assertSame(3.14, $param->getValue());
        $this->assertSame(ParamTypeEnum::Float, $this->getParamType($param));
    }

    public function testConstructorWithString(): void
    {
        $param = new Param("hello");
        
        $this->assertSame("hello", $param->getValue());
        $this->assertSame(ParamTypeEnum::String, $this->getParamType($param));
    }

    public function testConstructorWithBool(): void
    {
        $param = new Param(true);
        
        $this->assertSame(true, $param->getValue());
        $this->assertSame(ParamTypeEnum::Bool, $this->getParamType($param));
    }

    public function testConstructorWithArray(): void
    {
        $array = ['key1' => 'value1', 'key2' => 42];
        $param = new Param($array);
        
        $this->assertInstanceOf(ParamBag::class, $param->getValue());
        $this->assertEquals(ParamTypeEnum::Bag, $this->getParamType($param));
    }

    public function testConstructorWithParamBag(): void
    {
        $bag = new ParamBag();
        $bag['key'] = new Param('value');
        
        $param = new Param($bag);
        
        $this->assertInstanceOf(ParamBag::class, $param->getValue());
        $this->assertEquals(ParamTypeEnum::Bag, $this->getParamType($param));
    }

    public function testConstructorWithExplicitType(): void
    {
        $param = new Param(42, ParamTypeEnum::Int);
        
        $this->assertEquals(42, $param->getValue());
        $this->assertEquals(ParamTypeEnum::Int, $this->getParamType($param));
    }

    public function testConstructorWithInvalidType(): void
    {
        $this->expectException(TypeError::class);
        
        new Param("not_a_number", ParamTypeEnum::Int);
    }

    public function testGetValue(): void
    {
        $param = new Param("test_value");
        
        $this->assertEquals("test_value", $param->getValue());
    }

    public function testSetValueWithInt(): void
    {
        $param = new Param(42);
        $param->setValue(100);
        
        $this->assertEquals(100, $param->getValue());
    }

    public function testSetValueWithFloat(): void
    {
        $param = new Param(42.2);
        $param->setValue(3.14);

        $this->assertEquals(3.14, $param->getValue());
    }

    public function testSetValueWithBool(): void
    {
        $param = new Param(true);
        $param->setValue(false);

        $this->assertFalse($param->getValue());
    }

    public function testSetValueWithIncompatibleTypeIfSetToInt(): void
    {
        $param = new Param(42);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Value type must match ParamType");
        
        $param->setValue("string");
    }


    public function testSetValueWithIncompatibleTypeIfSetToString(): void
    {
        $param = new Param("42");

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Value type must match ParamType");

        $param->setValue(13);
    }


    public function testSetValueWithIncompatibleTypeIfSetToFloat(): void
    {
        $param = new Param(3.14);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Value type must match ParamType");

        $param->setValue("string");
    }


    public function testSetValueWithIncompatibleTypeIfSetToBool(): void
    {
        $param = new Param(false);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Value type must match ParamType");

        $param->setValue("string");
    }

    public function testSetValueWithIncompatibleTypeIfSetToBag(): void
    {
        $param = new Param([]);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Value type must match ParamType");

        $param->setValue("string");
    }

    public function testSetValueWithBag(): void
    {
        $param = new Param(['key' => 'value']);
        $param->setValue(['new_key' => 'new_value']);
        
        $this->assertInstanceOf(ParamBag::class, $param->getValue());
    }

    public function testAsInt(): void
    {
        $param = new Param(42);
        
        $this->assertSame(42, $param->asInt());
    }

    public function testAsIntWithFloatConversion(): void
    {
        $param = new Param(3.7);
        
        $this->assertSame(3, $param->asInt());
    }

    public function testAsIntWithStringConversion(): void
    {
        $param = new Param("42");
        
        $this->assertSame(42, $param->asInt());
    }

    public function testAsIntWithBoolConversion(): void
    {
        $param = new Param(true);
        
        $this->assertSame(1, $param->asInt());
    }

    public function testAsIntWithBag(): void
    {
        $param = new Param(['key' => 'value']);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter contains a bag");
        
        $param->asInt();
    }

    public function testAsFloat(): void
    {
        $param = new Param(3.14);
        
        $this->assertSame(3.14, $param->asFloat());
    }

    public function testAsFloatWithIntConversion(): void
    {
        $param = new Param(42);
        
        $this->assertSame(42.0, $param->asFloat());
    }

    public function testAsFloatWithStringConversion(): void
    {
        $param = new Param("3.14");
        
        $this->assertSame(3.14, $param->asFloat());
    }

    public function testAsFloatWithBag(): void
    {
        $param = new Param(['key' => 'value']);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter contains a bag");
        
        $param->asFloat();
    }

    public function testAsString(): void
    {
        $param = new Param("hello");
        
        $this->assertSame("hello", $param->asString());
    }

    public function testAsStringWithIntConversion(): void
    {
        $param = new Param(42);
        
        $this->assertSame("42", $param->asString());
    }

    public function testAsStringWithFloatConversion(): void
    {
        $param = new Param(3.14);
        
        $this->assertSame("3.14", $param->asString());
    }

    public function testAsStringWithBoolConversion(): void
    {
        $param = new Param(true);
        
        $this->assertSame("1", $param->asString());
    }

    public function testAsStringWithBag(): void
    {
        $param = new Param(['key' => 'value']);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter contains a bag");
        
        $param->asString();
    }

    public function testAsBool(): void
    {
        $param = new Param(true);
        
        $this->assertTrue($param->asBool());
    }

    public function testAsBoolWithIntConversion(): void
    {
        $param = new Param(42);
        
        $this->assertTrue($param->asBool());
    }

    public function testAsBoolWithZeroConversion(): void
    {
        $param = new Param(0);
        
        $this->assertFalse($param->asBool());
    }

    public function testAsBoolWithStringConversion(): void
    {
        $param = new Param("true");
        
        $this->assertTrue($param->asBool());
    }

    public function testAsBoolWithBag(): void
    {
        $param = new Param(['key' => 'value']);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter contains a bag");
        
        $param->asBool();
    }

    public function testGetParam(): void
    {
        $param = new Param(['key' => 'value']);
        $result = $param->getParam('key');

        $this->assertEquals('value', $result->getValue());
    }

    public function testGetParamWithDefault(): void
    {
        $param = new Param(['key' => 'value']);
        $result = $param->getParam('non_existent', 'default_value');

        $this->assertSame('default_value', $result->getValue());
    }

    public function testGetParamWithIntKey(): void
    {
        $param = new Param([0 => 'first', 1 => 'second']);
        $result = $param->getParam(0);
        
        $this->assertSame('first', $result->getValue());
    }

    public function testGetParamOnNonBag(): void
    {
        $param = new Param("scalar_value");
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter is not multi-dimensional");
        
        $param->getParam('key');
    }

    public function testGetParamOnIntParam(): void
    {
        $param = new Param(42);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Parameter is not multi-dimensional");
        
        $param->getParam('key');
    }

    public function testNestedArrayConversion(): void
    {
        $array = [
            'user' => [
                'name' => 'John',
                'age' => 30
            ]
        ];
        $param = new Param($array);
        
        $userParam = $param->getParam('user');
        $this->assertInstanceOf(Param::class, $userParam);
    }

    public function testMultipleSetValueOperations(): void
    {
        $param = new Param([]);
        $param->setValue(['key1' => 'value1']);
        
        $this->assertInstanceOf(ParamBag::class, $param->getValue());
        
        $param->setValue(['key2' => 'value2']);
        
        $this->assertInstanceOf(ParamBag::class, $param->getValue());
    }

    /**
     * Helper method to access private paramType property
     */
    private function getParamType(Param $param): ParamTypeEnum
    {
        $reflection = new \ReflectionClass($param);
        $property = $reflection->getProperty('paramType');
        $property->setAccessible(true);
        
        return $property->getValue($param);
    }
}