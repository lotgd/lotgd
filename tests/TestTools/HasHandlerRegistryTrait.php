<?php
declare(strict_types=1);

namespace LotGD2\Tests\TestTools;

use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;

trait HasHandlerRegistryTrait
{
    /**
     * @template THandler of object
     * @param array<class-string<THandler>, THandler> $handlerList
     * @return HandlerRegistry
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createHandlerRegistry(
        array $handlerList = [],
    ): HandlerRegistry {
        $returnMap = [];

        foreach ($handlerList as $class => $object) {
            $returnMap[] = [$class, $object];
        }

        $handlerRegistry = $this->createStub(HandlerRegistry::class);
        $handlerRegistry->method("get")->willReturnMap($returnMap);

        return $handlerRegistry;
    }
}