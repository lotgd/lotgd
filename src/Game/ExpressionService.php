<?php
declare(strict_types=1);

namespace LotGD2\Game;

use ErrorException;
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


/**
 * @phpstan-type StringExpressionLimit array{
 *     type: "string",
 * }
 * @phpstan-type IntExpressionLimit array{
 *     type: "int",
 *     min?: int,
 *     max?: int,
 * }
 * @phpstan-type ExpressionLimit StringExpressionLimit|IntExpressionLimit
 */
class ExpressionService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {

    }

    /**
     * @return array<string, ExpressionLimit|array<string, ExpressionLimit>>
     */
    public function getNamespace(): array
    {
        return [
            "character" => [
                "name" => [
                    "type" => "string",
                ],
                "level" => [
                    "type" => "int",
                    "min" => 1,
                    "max" => 15,
                ],
            ],
            "health" => [
                "health" => [
                    "type" => "int",
                    "min" => 0,
                    "max" => 150,
                ],
                "maxHealth" => [
                    "type" => "int",
                    "min" => 10,
                    "max" => 150,
                ]
            ],
            "stats" => [
                "experience" => [
                    "type" => "int",
                    "min" => 0,
                    "max" => 50000,
                ],
                "required" => [
                    "type" => "int",
                    "min" => 100,
                    "max" => 50000,
                ],
                "attack" => [
                    "type" => "int",
                    "min" => 1,
                    "max" => 30,
                ],
                "defense" => [
                    "type" => "int",
                    "min" => 1,
                    "max" => 30,
                ],
            ],
            "gold" => [
                "type" => "int",
                "min" => 0,
                "max" => 100000,
            ],
            "equipment" => [
                "weapon"  => [
                    "type" => "string",
                ],
                "armor"  => [
                    "type" => "string",
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getNames(bool $deep = false): array
    {
        $namespace = $this->getNamespace();

        if (!$deep) {
            return array_keys($namespace);
        }

        $objectNamespace = [];
        foreach ($namespace as $object => $properties) {
            if (is_array($properties) && count($properties) > 1 && !isset($properties["type"])) {
                foreach ($properties as $property => $propertyLimits) {
                    $objectNamespace[] = "$object.$property";
                }
            } else {
                $objectNamespace[] = $object;
            }
        }

        return $objectNamespace;
    }

    /**
     * @param string|null $expression
     * @return array{min: int|float, max: int|float}
     */
    public function evaluateMinMax(?string $expression): array
    {
        return [
            "min" => $this->evaluateMin($expression),
            "max" => $this->evaluateMax($expression),
        ];
    }

    public function evaluateMax(?string $expression): int|float|null
    {
        if ($expression === null || strlen($expression) === 0) {
            return null;
        }

        $namespace = $this->getNamespace();
        $names = [];

        foreach ($namespace as $object => $properties) {
            if (is_array($properties) && count($properties) > 1 && !isset($properties["type"])) {
                $names[$object] = [];
                foreach ($properties as $property => $propertyLimits) {
                    if (isset($propertyLimits["max"])) {
                        $names[$object][$property] = $propertyLimits["max"];
                    }
                }

                $names[$object] = (object)$names[$object];
            } else {
                if (isset($properties["max"])) {
                    $names[$object] = $properties["max"];
                }
            }
        }

        $result = $this->_evaluate($expression, $names);
        return is_numeric($result) ? $result : null;
    }

    public function evaluateMin(?string $expression): int|float|null
    {
        if ($expression === null || strlen($expression) === 0) {
            return null;
        }

        $namespace = $this->getNamespace();
        $names = [];

        foreach ($namespace as $object => $properties) {
            if (is_array($properties) && count($properties) > 1 && !isset($properties["type"])) {
                $names[$object] = [];
                foreach ($properties as $property => $propertyLimits) {
                    if (isset($propertyLimits["min"])) {
                        $names[$object][$property] = $propertyLimits["min"];
                    }
                }

                $names[$object] = (object)$names[$object];
            } else {
                if (isset($properties["min"])) {
                    $names[$object] = $properties["min"];
                }
            }
        }

        $result = $this->_evaluate($expression, $names);
        return is_numeric($result) ? $result : null;
    }

    /**
     * @param Character $character
     * @return array<string, object|scalar>
     */
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

        return $this->_evaluate($expression, $this->getCharacterBasedNames($character));
    }

    private function _evaluate(?string $expression, array $names)
    {
        $expressionLanguage = new ExpressionLanguage();
        $flags = Parser::IGNORE_UNKNOWN_VARIABLES;

        try {
            $expressionLanguage->lint($expression, $names, $flags);
            return $expressionLanguage->evaluate($expression, $names);
        } catch (SyntaxError|ErrorException $e) {
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