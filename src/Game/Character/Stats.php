<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class Stats
{
    const string ExperiencePropertyName = 'experience';
    const string LevelPropertyName = 'level';
    const string AttackPropertyName = 'attack';
    const string DefensePropertyName = 'defense';

    public function __construct(
        private ?LoggerInterface $logger,
        private Equipment $equipment,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getExperience(): int
    {
        return $this->character->getProperty(self::ExperiencePropertyName, 0);
    }

    public function setExperience(int $experience): static
    {
        $this->logger->debug("{$this->character->getId()}: set experience to {$experience}");
        $this->character->setProperty(self::ExperiencePropertyName, $experience);
        return $this;
    }

    public function addExperience(int $experience): static
    {
        $this->logger->debug("{$this->character->getId()}:: adding experience {$experience}");
        $this->setExperience($this->getExperience() + $experience);
        return $this;
    }

    public function getLevel(): int
    {
        return $this->character->getLevel();
    }

    public function getAttack(): int
    {
        return $this->character->getProperty(self::AttackPropertyName, 1);
    }

    public function getDefense(): int
    {
        return $this->character->getProperty(self::DefensePropertyName, 1);
    }

    public function getTotalAttack(): int
    {
        return $this->getAttack() + ($this->equipment->getItemInSlot(Equipment::WeaponSlot)?->getValue() ?? 0);
    }

    public function getTotalDefense(): int
    {
        return $this->getDefense() + ($this->equipment->getItemInSlot(Equipment::ArmorSlot)?->getValue() ?? 0);
    }
}