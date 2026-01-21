<?php
declare(strict_types=1);

namespace LotGD2\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

class PropertyHookSet {
    public ?int $integer = null {
        get => $this->integer;
        set(string|float|int|bool|null $value) {
            $this->integer = (int)$value;
        }
    }
}

#[CoversClass(PropertyHookSet::class)]
class PhpUnitTest extends TestCase
{
    public function testNormalUsage()
    {
        $propertyHook = new PropertyHookSet();
        $propertyHook->integer = 5;

        $this->assertSame(5, $propertyHook->integer);

        $propertyHook->integer = "5";

        $this->assertSame(5, $propertyHook->integer);
    }

    #[DoesNotPerformAssertions]
    public function testSomething()
    {
        $mock = $this->createMock(PropertyHookSet::class);
    }
}