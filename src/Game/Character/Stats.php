<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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

    public function getExperience(?Character $character = null): int
    {
        $character ??= $this->character;
        return $character->getProperty(self::ExperiencePropertyName, 0);
    }

    public function setExperience(int $experience, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->logger->debug("{$character->id}: set experience to {$experience}");
        $character->setProperty(self::ExperiencePropertyName, $experience);
        return $this;
    }

    public function addExperience(int $experience, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->setExperience($this->getExperience() + $experience, $character);
        return $this;
    }

    public function getRequiredExperience(?Character $character = null): ?int
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

        $character ??= $this->character;
        return $requiredExperience[$character->level - 1] ?? null;
    }

    public function getLevel(?Character $character = null): int
    {
        $character ??= $this->character;
        return $character->level;
    }

    public function getAttack(?Character $character = null): int
    {
        $character ??= $this->character;
        return $character->getProperty(self::AttackPropertyName, 1);
    }

    public function getTotalAttack(?Character $character = null): int
    {
        $character ??= $this->character;
        return $this->getAttack($character) + ($this->equipment->getItemInSlot(Equipment::WeaponSlot, $character)?->getStrength() ?? 0);
    }

    public function setAttack(int $attack, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->logger?->debug("{$character->id}: attack set to {$attack} (was {$this->getAttack($character)}) before).");
        $character->setProperty(self::AttackPropertyName, $attack);
        return $this;
    }

    public function addAttack(int $attack, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->setAttack($this->getAttack($character) + $attack, $character);
        return $this;
    }

    public function getDefense(?Character $character = null): int
    {
        $character ??= $this->character;
        return $character->getProperty(self::DefensePropertyName, 1);
    }

    public function setDefense(int $defense, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->logger?->debug("{$character->id}: defense set to {$defense} (was {$this->getDefense($character)}) before).");
        $character->setProperty(self::DefensePropertyName, $defense);
        return $this;
    }

    public function addDefense(int $defense, ?Character $character = null): static
    {
        $character ??= $this->character;
        $this->setDefense($this->getDefense() + $defense, $character);
        return $this;
    }

    public function getTotalDefense(?Character $character = null): int
    {
        $character ??= $this->character;
        return $this->getDefense($character) + ($this->equipment->getItemInSlot(Equipment::ArmorSlot, $character)?->getStrength() ?? 0);
    }

    public function levelUp(?Character $character = null): static
    {
        $character ??= $this->character;
        $character->level = $character->level + 1;

        $this->logger?->debug("{$character->id}: Level increased to {$character->level}.");

        $this->addAttack(1, $character);
        $this->addDefense(1, $character);
        $this->health->addMaxHealth(10, $character);

        return $this;
    }

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $deltaLevel = $event->characterBefore->level - $event->character->level;

        if ($deltaLevel > 0) {
            $deltaIncrease = $deltaLevel;
            $this->addAttack(-$deltaIncrease, $event->character);
            $this->addDefense(-$deltaIncrease, $event->character);
        }

        $this->setExperience(0, $event->character);
    }
}