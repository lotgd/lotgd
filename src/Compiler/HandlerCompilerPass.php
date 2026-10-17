<?php
declare(strict_types=1);

namespace LotGD2\Compiler;

use LotGD2\Game\Handler\HandlerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class HandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(HandlerRegistry::class)) {
            return;
        }

        $handlerRegistry = $container->getDefinition(HandlerRegistry::class);

        foreach ($container->findTaggedServiceIds('lotgd.property_handler') as $id => $tags) {
            // A service could have the same tag twice
            foreach ($tags as $attributes) {
                $handlerRegistry->addMethodCall("addHandler", [
                    $attributes["name"] ?? $id,
                    new Reference($id),
                ]);
            }
        }
    }
}
