<?php
declare(strict_types=1);

namespace LotGD2;

use LotGD2\Attribute\PropertyHandler;
use LotGD2\Compiler\HandlerCompilerPass;
use Reflector;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            attributeClass: PropertyHandler::class,
            configurator: static function(
                ChildDefinition $definition,
                PropertyHandler $attribute,
                Reflector       $reflector
            ): void {
                $attributes = [];

                if ($attribute->name !== null) {
                    $attributes["name"] = $attribute->name;
                }

                $definition->addTag("lotgd.property_handler", $attributes);
            }
        );

        $container->addCompilerPass(new HandlerCompilerPass());
    }
}
