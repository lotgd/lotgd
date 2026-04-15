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
        private Character $character,
        private HealthHandler $health,
        private StatsHandler $stats,
        private GoldHandler $gold,
        private EquipmentHandler $equipment,
    ) {

    }

    public function evaluate(?string $expression): bool
    {
        if ($expression === null || strlen($expression) === 0) {
            return true;
        }

        $expressionLanguage = new ExpressionLanguage();
        $names = [
            "character" => (object)[
                "name" => $this->character->name,
                "level" => $this->character->level,
            ],
            "health" => (object)[
                "health" => $this->health->getHealth(),
                "maxHealth" => $this->health->getMaxHealth()
            ],
            "stats" => (object)[
                "experience" => $this->stats->getExperience(),
                "required" => $this->stats->getRequiredExperience(),
            ],
            "gold" => $this->gold->getGold(null),
            "equipment" => (object)[
                "weapon" => $this->equipment->getName(EquipmentHandler::WeaponSlot),
                "armor" => $this->equipment->getName(EquipmentHandler::ArmorSlot),
            ]
        ];

        $flags = Parser::IGNORE_UNKNOWN_VARIABLES;

        try {
            $expressionLanguage->lint($expression, $names, $flags);
            return (bool)$expressionLanguage->evaluate($expression, $names);
        } catch (SyntaxError $e) {
            // Allow connection to be made if expression contains an error
            $this->logger->warning("Expression was faulty: {$expression}. {$e->getMessage()}");
            return true;
        }
    }
}