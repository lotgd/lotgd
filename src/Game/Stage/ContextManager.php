<?php
declare(strict_types=1);

namespace LotGD2\Game\Stage;

use LotGD2\Game\ExpressionService;
use LotGD2\Game\GameLoop;

class ContextManager
{
    public function __construct(
        private readonly ExpressionService $expressionService,
        private readonly GameLoop $gameLoop,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultContext(): array
    {
        $character = $this->gameLoop->getCharacter();

        if ($character) {
            return $this->expressionService->getCharacterBasedNames($character);
        } else {
            return [];
        }
    }
}