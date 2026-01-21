<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Param;

use LotGD2\Entity\Param\ParamBag;
use LotGD2\Entity\Param\Param;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParamBag::class)]
#[UsesClass(Param::class)]
class ParamBagTest extends TestCase
{
    private ParamBag $bag;

    protected function setUp(): void
    {
        $this->bag = new ParamBag();
    }

    public function testConstructor(): void
    {
        $bag = new ParamBag();
        $this->assertEmpty($bag->params);
    }

    public function testOffsetSet(): void
    {
        $this->bag['key'] = 'value';
        $this->assertArrayHasKey('key', $this->bag->params);
        $this->assertInstanceOf(Param::class, $this->bag->params['key']);
    }

    public function testOffsetSetWithParam(): void
    {
        $param = new Param('test_value');
        $this->bag['key'] = $param;
        $this->assertSame($param, $this->bag->params['key']);
    }

    public function testOffsetSetWithArray(): void
    {
        $array = ['nested_key' => 'nested_value'];
        $this->bag['key'] = $array;
        $this->assertInstanceOf(Param::class, $this->bag->params['key']);
    }

    public function testOffsetSetWithParamBag(): void
    {
        $nestedBag = new ParamBag();
        $nestedBag['nested_key'] = 'nested_value';
        $this->bag['key'] = $nestedBag;
        $this->assertInstanceOf(Param::class, $this->bag->params['key']);
    }

    public function testOffsetExists(): void
    {
        $this->bag['key'] = 'value';
        $this->assertTrue($this->bag->offsetExists('key'));
        $this->assertFalse($this->bag->offsetExists('nonexistent'));
    }

    public function testOffsetGet(): void
    {
        $this->bag['key'] = 'value';
        $this->assertEquals('value', $this->bag['key']);
    }

    public function testOffsetUnset(): void
    {
        $this->bag['key'] = 'value';
        $this->assertTrue(isset($this->bag['key']));
        
        unset($this->bag['key']);
        $this->assertFalse(isset($this->bag['key']));
    }

    public function testGetParam(): void
    {
        $this->bag['key'] = 'value';
        $param = $this->bag->getParam('key');
        
        $this->assertInstanceOf(Param::class, $param);
        $this->assertEquals('value', $param->getValue());
    }

    public function testGetParamNotExists(): void
    {
        $param = $this->bag->getParam('nonexistent');
        $this->assertNull($param);
    }

    public function testGetParamWithDefault(): void
    {
        $param = $this->bag->getParam('nonexistent', 'default_value');
        $this->assertInstanceOf(Param::class, $param);
        $this->assertEquals('default_value', $param->getValue());
    }

    public function testGetParamArray(): void
    {
        $this->bag['key1'] = 'value1';
        $this->bag['key2'] = 'value2';
        
        $paramArray = $this->bag->getParamArray();
        
        $this->assertCount(2, $paramArray);
        $this->assertArrayHasKey('key1', $paramArray);
        $this->assertArrayHasKey('key2', $paramArray);
        $this->assertInstanceOf(Param::class, $paramArray['key1']);
    }

    public function testSetParamArray(): void
    {
        $param1 = new Param('value1');
        $param2 = new Param('value2');
        $paramArray = ['key1' => $param1, 'key2' => $param2];
        
        $result = $this->bag->setParamArray($paramArray);
        
        $this->assertSame($this->bag, $result);
        $this->assertCount(2, $this->bag->params);
        $this->assertSame($param1, $this->bag->params['key1']);
        $this->assertSame($param2, $this->bag->params['key2']);
    }

    public function testNestedParamBag(): void
    {
        $this->bag['outer'] = ['inner' => 'value'];
        
        $this->assertInstanceOf(ParamBag::class, $this->bag['outer']);
        $this->assertEquals('value', $this->bag['outer']['inner']);
    }

    public function testMultipleScalarTypes(): void
    {
        $this->bag['string'] = 'text';
        $this->bag['int'] = 42;
        $this->bag['float'] = 3.14;
        $this->bag['bool'] = true;
        
        $this->assertEquals('text', $this->bag['string']);
        $this->assertEquals(42, $this->bag['int']);
        $this->assertEquals(3.14, $this->bag['float']);
        $this->assertTrue($this->bag['bool']);
    }

    public function testArrayAccess(): void
    {
        $this->bag['key'] = 'value';
        
        // Test isset
        $this->assertTrue(isset($this->bag['key']));
        
        // Test unset
        unset($this->bag['key']);
        $this->assertFalse(isset($this->bag['key']));
    }
}