<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

/**
 * Entity for a single battle message with its context.
 */
class BattleMessage
{
    /**
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function __construct(
        private(set) string $message,
        private(set) array $context,
    ) {

    }
}