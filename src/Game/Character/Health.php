<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class Health
{
    const string HealthPropertyName = 'health';
    const string MaxHealthPropertyName = 'maxHealth';

    public function __construct(
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getHealth(): int
    {
        return $this->character->getProperty(self::HealthPropertyName, 10);
    }

    public function setHealth(int $health): self
    {
        $this->logger?->debug("{$this->character->getId()}: health set to {$health} (was {$this->getHealth()}) before).");
        $this->character->setProperty(self::HealthPropertyName, $health);
        return $this;
    }

    /**
     * Increases the health of a Character by a given amount.
     *
     * If the health surpasses the character's max health, the health is limited to the maximum.
     * If the health is going into negative, the character's health is set to 0 instead.
     * @param int|null $health The amount to heal the character. If null is given, character is healed completely.
     * @return $this
     */
    public function heal(?int $health = null): self
    {
        if ($health === null) {
            $health = $this->getMaxHealth();
        }

        $this->logger?->debug("{$this->character->getId()}: healed by {$health}.");
        $this->character->setProperty(
            self::HealthPropertyName,
            min(
                $this->character->getProperty(self::MaxHealthPropertyName),
                max(
                    $this->getHealth() + $health,
                    0
                )
            )
        );
        return $this;
    }

    public function getMaxHealth(): int
    {
        return $this->character->getProperty(self::MaxHealthPropertyName, 10);
    }

    public function setMaxHealth(int $maxHealth): self
    {
        $this->logger?->debug("{$this->character->getId()}: health set to {$maxHealth} (was {$this->getMaxHealth()}) before).");
        $this->character->setProperty(self::MaxHealthPropertyName, $maxHealth);
        return $this;
    }

    public function isAlive(): bool
    {
        return $this->character->getProperty(self::HealthPropertyName) > 0;
    }
}