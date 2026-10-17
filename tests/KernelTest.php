<?php
declare(strict_types=1);

namespace LotGD2\Tests;

use LotGD2\Attribute\PropertyHandler;
use LotGD2\Compiler\HandlerCompilerPass;
use LotGD2\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(Kernel::class)]
#[UsesClass(PropertyHandler::class)]
class KernelTest extends TestCase
{
    private function createKernel(): Kernel
    {
        return new class('test', true) extends Kernel {
            public function build(ContainerBuilder $container): void
            {
                parent::build($container);
            }
        };
    }

    public function testHandlerCompilerPassIsAdded(): void
    {
        $kernel = $this->createKernel();
        $container = new ContainerBuilder();

        $kernel->build($container);

        $passes = $container->getCompilerPassConfig()->getPasses();
        $handlerPasses = array_filter(
            $passes,
            static fn(object $pass): bool => $pass instanceof HandlerCompilerPass
        );

        $this->assertNotEmpty($handlerPasses);
    }

    public function testRegisterAttributeForAutoconfigurationIsCalledForPropertyHandler(): void
    {
        $kernel = $this->createKernel();
        $container = new ContainerBuilder();

        $kernel->build($container);

        $autoconfiguredAttributes = $container->getAttributeAutoconfigurators();

        $this->assertArrayHasKey(PropertyHandler::class, $autoconfiguredAttributes);
        $this->assertNotEmpty($autoconfiguredAttributes[PropertyHandler::class]);
    }

    public function testPropertyHandlerConfiguratorWithoutName(): void
    {
        $kernel = $this->createKernel();
        $container = new ContainerBuilder();

        $kernel->build($container);

        $autoconfiguredAttributes = $container->getAttributeAutoconfigurators();
        $configurator = $autoconfiguredAttributes[PropertyHandler::class][0];

        $definition = new ChildDefinition('');
        $attribute = new PropertyHandler();
        $reflector = new ReflectionClass(stdClass::class);

        $configurator($definition, $attribute, $reflector);

        $this->assertTrue($definition->hasTag('lotgd.property_handler'));
        $this->assertSame([[]], $definition->getTag('lotgd.property_handler'));
    }

    public function testPropertyHandlerConfiguratorWithName(): void
    {
        $kernel = $this->createKernel();
        $container = new ContainerBuilder();

        $kernel->build($container);

        $autoconfiguredAttributes = $container->getAttributeAutoconfigurators();
        $configurator = $autoconfiguredAttributes[PropertyHandler::class][0];

        $definition = new ChildDefinition('');
        $attribute = new PropertyHandler('custom_handler');
        $reflector = new ReflectionClass(stdClass::class);

        $configurator($definition, $attribute, $reflector);

        $this->assertTrue($definition->hasTag('lotgd.property_handler'));
        $this->assertSame([['name' => 'custom_handler']], $definition->getTag('lotgd.property_handler'));
    }
}
