<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

class HandlerRegistry
{
    /**
     * @var array<string, object>
     */
    private array $registry = [];

    public function addHandler(string $name, object $handler): void
    {
        $this->registry[$name] = $handler;
    }

    /**
     * @template THandler of object
     * @param class-string<THandler> $name
     * @return THandler
     */
    public function get(string $name): object
    {
        /** @var THandler $object */
        $object = $this->registry[$name];
        return $object;
    }
}