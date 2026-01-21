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
        private Health $health,
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
        $this->setExperience($this->getExperience() + $experience);
        return $this;
    }

    public function getRequiredExperience(): ?int
    {
        $requiredExperience = [
            100,
            400,
            1002,
            1912,
            3140,
            4707,
            6641,
            8985,
            11795,
            15143,
            19121,
            23840,
            29437,
            36071,
            43930,
        ];

        return $requiredExperience[$this->character->getLevel() - 1] ?? null;
    }

    public function getLevel(): int
    {
        return $this->character->getLevel();
    }

    public function getAttack(): int
    {
        return $this->character->getProperty(self::AttackPropertyName, 1);
    }

    public function getTotalAttack(): int
    {
        return $this->getAttack() + ($this->equipment->getItemInSlot(Equipment::WeaponSlot)?->getStrength() ?? 0);
    }

    public function setAttack(int $attack): static
    {
        $this->logger?->debug("{$this->character->getId()}: attack set to {$attack} (was {$this->getAttack()}) before).");
        $this->character->setProperty(self::AttackPropertyName, $attack);
        return $this;
    }

    public function addAttack(int $attack): static
    {
        $this->setAttack($this->getAttack() + $attack);
        return $this;
    }

    public function getDefense(): int
    {
        return $this->character->getProperty(self::DefensePropertyName, 1);
    }

    public function setDefense(int $defense): static
    {
        $this->logger?->debug("{$this->character->getId()}: defense set to {$defense} (was {$this->getDefense()}) before).");
        $this->character->setProperty(self::DefensePropertyName, $defense);
        return $this;
    }

    public function addDefense(int $defense): static
    {
        $this->setDefense($this->getDefense() + $defense);
        return $this;
    }

    public function getTotalDefense(): int
    {
        return $this->getDefense() + ($this->equipment->getItemInSlot(Equipment::ArmorSlot)?->getStrength() ?? 0);
    }

    public function levelUp(): static
    {
        $this->character->setLevel($this->character->getLevel() + 1);

        $this->logger?->debug("{$this->character->getId()}: Level increased to {$this->character->getLevel()}.");

        $this->addAttack(1);
        $this->addDefense(1);
        $this->health->addMaxHealth(10);

        return $this;
    }
}