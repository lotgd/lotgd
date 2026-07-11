<?php
declare(strict_types=1);

namespace LotGD2\Game;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\SyntaxError;


class ExpressionService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {

    }

    /**
     * @return string[],
     */
    public function getNames(): array
    {
        return [
            "character" => [
                "name",
                "level",
            ],
            "health" => [
                "health",
                "maxHealth",
            ],
            "stats",
            "gold",
            "equipment",
        ];
    }

    public function getCharacterBasedNames(Character $character): array
    {
        $healthHandler = new HealthHandler($this->logger, $character);
        $equipmentHandler = new EquipmentHandler($this->logger, $character);
        $statsHandler = new StatsHandler($this->logger, $equipmentHandler, $character);
        $goldHandler = new GoldHandler($this->logger, $character);

        return [
            "character" => (object)[
                "name" => $character->name,
                "level" => $character->level,
            ],
            "health" => (object)[
                "health" => $healthHandler->getHealth($character),
                "maxHealth" => $healthHandler->getMaxHealth($character)
            ],
            "stats" => (object)[
                "experience" => $statsHandler->getExperience($character),
                "required" => $statsHandler->getRequiredExperience($character),
                "attack" => $statsHandler->getTotalAttack($character),
                "defense" => $statsHandler->getTotalDefense($character),
            ],
            "gold" => $goldHandler->getGold($character),
            "equipment" => (object)[
                "weapon" => $equipmentHandler->getName(EquipmentHandler::WeaponSlot, $character),
                "armor" => $equipmentHandler->getName(EquipmentHandler::ArmorSlot, $character),
            ]
        ];
    }

    public function evaluate(Character $character, ?string $expression): mixed
    {
        if ($expression === null || strlen($expression) === 0) {
            return null;
        }

        $expressionLanguage = new ExpressionLanguage();
        $names = $this->getCharacterBasedNames($character);

        $flags = Parser::IGNORE_UNKNOWN_VARIABLES;

        try {
            $expressionLanguage->lint($expression, $names, $flags);
            return $expressionLanguage->evaluate($expression, $names);
        } catch (SyntaxError $e) {
            // Allow connection to be made if expression contains an error
            $this->logger->warning("Expression was faulty: {$expression}. {$e->getMessage()}");
            return null;
        }
    }

    public function evaluateBoolean(Character $character, ?string $expression, bool $default = true): bool
    {
        $value = $this->evaluate($character, $expression);

        if ($value === null) {
            return $default;
        } else {
            return (bool)$value;
        }
    }

    public function evaluateInteger(Character $character, ?string $expression, int $default = 0): int
    {
        $value = $this->evaluate($character, $expression);

        if ($value === null) {
            return $default;
        } else {
            return (int)round($value);
        }
    }

    public function evaluateFloat(Character $character, ?string $expression, float $default = 1.): float
    {
        $value = $this->evaluate($character, $expression);

        if ($value === null) {
            return $default;
        } else {
            return (float)$value;
        }
    }
}