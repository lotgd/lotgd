<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class Health
{
    const string HealthPropertyName = 'health';
    const string MaxHealthPropertyName = 'maxHealth';
    const string Resurrections = "resurrections";
    const string Age = "age";
    const string Turns = "turns";
    const string MaxTurns = "maxTurns";

    public function __construct(
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getHealth(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::HealthPropertyName, $character->level * 10);
    }

    public function setHealth(int $health, ?Character $character = null): static
    {
        $character = $character ?? $this->character;
        $this->logger?->debug("{$character->id}: health set to {$health} (was {$this->getHealth($character)}) before).");
        $character->setProperty(static::HealthPropertyName, $health);
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
    public function heal(?int $health = null, ?Character $character = null): static
    {
        $character = $character ?? $this->character;
        if ($health === null) {
            $health = $this->getMaxHealth($character);
        }

        $this->logger?->debug("{$character->id}: healed by {$health}.");
        $character->setProperty(
            static::HealthPropertyName,
            min(
                $character->getProperty(static::MaxHealthPropertyName),
                max(
                    $this->getHealth($character) + $health,
                    0
                )
            )
        );

        return $this;
    }

    public function getMaxHealth(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::MaxHealthPropertyName, $character->level * 10);
    }

    public function setMaxHealth(int $maxHealth, ?Character $character = null): static
    {
        $character = $character ?? $this->character;
        $this->logger?->debug("{$character->id}: health set to {$maxHealth} (was {$this->getMaxHealth($character)}) before).");
        $character->setProperty(static::MaxHealthPropertyName, $maxHealth);
        return $this;
    }

    public function addMaxHealth(int $maxHealth, ?Character $character = null): static
    {
        $character = $character ?? $this->character;
        $this->setMaxHealth($this->getMaxHealth($character) + $maxHealth, $character);
        return $this;
    }

    public function isAlive(?Character $character = null): bool
    {
        return $this->getHealth($character ?? $this->character) > 0;
    }

    public function getResurrections(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::Resurrections, 10);
    }

    public function addResurrection(?Character $character = null): self
    {
        $character = $character ?? $this->character;
        $character->setProperty(self::Resurrections, $this->getResurrections($character) + 1);

        $this->logger?->debug("{$character->id}: resurrections incremented to {$this->getTurns($character)}.");
        return $this;
    }

    public function getAge(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::Age, 0);
    }

    public function addAge(?Character $character = null): self
    {
        $character = $character ?? $this->character;
        $character->setProperty(self::Age, $this->getAge($character) + 1);

        $this->logger?->debug("{$character->id}: age incremented to {$this->getTurns($character)}.");
        return $this;
    }

    public function getTurns(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::Turns, 30);
    }

    public function setTurns(?int $turns = null, ?Character $character = null): self
    {
        $character = $character ?? $this->character;
        $turns = $turns ?? $this->getMaxTurns($character);

        $this->logger?->debug("{$character->id}: turns set to {$turns} (was {$this->getTurns($character)}) before).");

        $character->setProperty(static::Turns, $turns);
        return $this;
    }

    public function addTurns(int $turns, ?Character $character): self
    {
        $character = $character ?? $this->character;
        $character->setProperty(self::Turns, $this->getTurns($character) + $turns);
        return $this;
    }

    public function decrementTurns(?Character $character = null): self
    {
        $character = $character ?? $this->character;
        $character->setProperty(static::Turns, $this->getTurns($character) - 1);
        return $this;
    }

    public function getMaxTurns(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(static::MaxTurns, 30);
    }

    public function setMaxTurns(?int $turns, ?Character $character = null): self
    {
        $character = $character ?? $this->character;

        $this->logger?->debug("{$character->id}: maxTurns set to {$turns} (was {$this->getMaxTurns($character)}) before).");

        $character->setProperty(static::MaxTurns, $turns);
        return $this;
    }

    #[AsEventListener(event: NewDay::OnNewDayAfter)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        // Increase Age
        $this->addAge($event->character);

        $event->stage->addParagraph(new Paragraph(
            id: "lotgd.paragraph.Health.newDay.age",
            text: "You open your eyes to discover that a new day has been bestowed upon you. It is day number {{ age }}.",
            context: ["age" => $this->getAge($event->character)]
        ));

        // Reset turns
        $this->setTurns(character: $event->character);

        $event->stage->addParagraph(new Paragraph(
            id: "lotgd.paragraph.Health.newDay.turns",
            text: "Turns for today set to {{ turns }}.",
            context: ["turns" => $this->getTurns($event->character)]
        ));

        // Add a resurrection notice if the user was not alive prior to today
        if ($this->isAlive($event->character) === false) {
            $this->addResurrection($event->character);

            $event->stage->addParagraph(new Paragraph(
                id: "lotgd.paragraph.Health.newDay.resurrection",
                text: "You are resurrected! This is resurrection number {{ resurrections }}",
                context: ["resurrections" => $this->getResurrections($event->character)]
            ));
        }

        // Restore health completely
        $this->heal(character: $event->character);

        $event->stage->addParagraph(new Paragraph(
            id: "lotgd.paragraph.Health.newDay.heal",
            text: "Your health was restored to {{ maxHealth }}.",
            context: ["maxHealth" => $this->getMaxHealth($event->character)]
        ));
    }

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $deltaLevel = $event->characterBefore->level - $event->character->level;

        if ($deltaLevel > 0) {
            $deltaHealth = $deltaLevel * 10;
            $this->addMaxHealth(-$deltaHealth, $event->character);
            $this->heal(-$deltaHealth, $event->character);
        }
    }
}