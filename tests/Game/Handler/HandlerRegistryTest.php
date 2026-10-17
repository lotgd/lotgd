<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use LotGD2\Game\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerRegistry::class)]
class HandlerRegistryTest extends TestCase
{
    public function testAddAndGetHandler(): void
    {
        $registry = new HandlerRegistry();

        $object = new class() {

        };

        $registry->addHandler("test", $object);

        $this->assertSame($object, $registry->get("test"));
    }
}
