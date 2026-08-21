<?php
declare(strict_types=1);

namespace LotGD2\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @phpstan-template T of object
 */
class NewEntityEvent extends Event
{
    /**
     * @param T $entity
     */
    public function __construct(
        public readonly object $entity,
    ) {

    }
}