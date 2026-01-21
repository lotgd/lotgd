<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

/**
 * Entity to collect battle messages for a single round.
 */
class BattleRoundMessage
{
    /** @var array<int, BattleMessage> */
    public(set) array $messages = [];

    public function __construct(
        public(set) int $round,
    ) {

    }

    public function add(BattleMessage $message): void
    {
        $this->messages[] = $message;
    }
}