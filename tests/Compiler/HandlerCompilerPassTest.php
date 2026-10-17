<?php
declare(strict_types=1);

namespace LotGD2\Tests\Compiler;

use LotGD2\Compiler\HandlerCompilerPass;
use LotGD2\Game\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(HandlerCompilerPass::class)]
#[UsesClass(HandlerRegistry::class)]
class HandlerCompilerPassTest extends TestCase
{
    public function testProcessReturnsEarlyWhenHandlerRegistryIsNotDefined(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn(false);

        $container->expects($this->never())
            ->method('getDefinition');

        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $pass = new HandlerCompilerPass();
        $pass->process($container);
    }

    public function testProcessAddsTaggedServicesToHandlerRegistryWithoutCustomName(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $handlerRegistryDefinition = $this->createMock(Definition::class);

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn(true);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn($handlerRegistryDefinition);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('lotgd.property_handler')
            ->willReturn([
                'app.test_handler' => [[]],
            ]);

        $handlerRegistryDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addHandler', [
                'app.test_handler',
                new Reference('app.test_handler'),
            ]);

        $pass = new HandlerCompilerPass();
        $pass->process($container);
    }

    public function testProcessAddsTaggedServicesToHandlerRegistryWithCustomName(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $handlerRegistryDefinition = $this->createMock(Definition::class);

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn(true);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn($handlerRegistryDefinition);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('lotgd.property_handler')
            ->willReturn([
                'app.test_handler' => [['name' => 'custom_handler_name']],
            ]);

        $handlerRegistryDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addHandler', [
                'custom_handler_name',
                new Reference('app.test_handler'),
            ]);

        $pass = new HandlerCompilerPass();
        $pass->process($container);
    }

    public function testProcessHandlesMultipleServicesAndMultipleTags(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $handlerRegistryDefinition = $this->createMock(Definition::class);

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn(true);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(HandlerRegistry::class)
            ->willReturn($handlerRegistryDefinition);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('lotgd.property_handler')
            ->willReturn([
                'service_a' => [[]],
                'service_b' => [
                    ['name' => 'tag_1'],
                    ['name' => 'tag_2'],
                ],
            ]);

        $calls = [];
        $handlerRegistryDefinition->expects($this->exactly(3))
            ->method('addMethodCall')
            ->willReturnCallback(function (string $method, array $arguments) use (&$calls, $handlerRegistryDefinition): Definition {
                $calls[] = [$method, $arguments];
                return $handlerRegistryDefinition;
            });

        $pass = new HandlerCompilerPass();
        $pass->process($container);

        $this->assertEquals([
            ['addHandler', ['service_a', new Reference('service_a')]],
            ['addHandler', ['tag_1', new Reference('service_b')]],
            ['addHandler', ['tag_2', new Reference('service_b')]],
        ], $calls);
    }
}
