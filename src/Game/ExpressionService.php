<?php
declare(strict_types=1);

namespace LotGD2\Game;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
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
        private Health $health,
        private Stats $stats,
        private Gold $gold,
        private Equipment $equipment,
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
            "gold" => $this->gold->getGold(),
            "equipment" => (object)[
                "weapon" => $this->equipment->getName(Equipment::WeaponSlot),
                "armor" => $this->equipment->getName(Equipment::ArmorSlot),
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