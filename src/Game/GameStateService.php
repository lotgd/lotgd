<?php
declare(strict_types=1);

namespace LotGD2\Game;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\GameState;
use LotGD2\Game\Enum\GameStateType;
use LotGD2\Game\Error\GameError;
use LotGD2\Repository\GameStateRepository;

class GameStateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameStateRepository $gameStateRepository,
    ) {

    }

    public function getSetting(string $name, mixed $default = null): mixed
    {
        $gameState = $this->gameStateRepository->getSettingByName($name);

        if ($gameState and $gameState->type !== GameStateType::Setting) {
            throw new GameError("{$name} is a state, not a setting.");
        }

        return $gameState->state ?? $default;
    }

    public function setSetting(string $name, mixed $value): self
    {
        $gameState = $this->gameStateRepository->getSettingByName($name);

        if ($gameState === null) {
            $gameState = new GameState(
                name: $name,
                state: $value,
                type: GameStateType::Setting,
            );
        } else {
            if ($gameState->type !== GameStateType::Setting) {
                throw new GameError("{$name} is a state, not a setting.");
            }

            $gameState->state = $value;
        }

        $this->entityManager->persist($gameState);

        return $this;
    }

    public function getState(string $name, mixed $default = null): mixed
    {
        $gameState = $this->gameStateRepository->getSettingByName($name);

        if ($gameState->type !== GameStateType::State) {
            throw new GameError("{$name} is a setting, not a state.");
        }

        return $gameState->state ?? $default;
    }

    public function setState(string $name, mixed $value): self
    {
        $gameState = $this->gameStateRepository->getSettingByName($name);

        if ($gameState === null) {
            $gameState = new GameState(
                name: $name,
                state: $value,
                type: GameStateType::State,
            );
        } else {
            if ($gameState->type !== GameStateType::State) {
                throw new GameError("{$name} is a setting, not a state.");
            }
        }

        $this->entityManager->persist($gameState);

        return $this;
    }
}