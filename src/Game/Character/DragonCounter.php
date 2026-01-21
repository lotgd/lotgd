<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type DragonPointChoice array{
 *     choice: string,
 *     age?: int,
 * }&array<string, mixed>
 */
class DragonCounter
{
    const string CounterPropertyName = "dragonCounter";
    const string ChoicePropertyName = "dragonCounterChoice";

    public function __construct(
        readonly private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        readonly private Character $character,
    ) {
    }

    public int $dragonCounter {
        get {
            return $this->character->getProperty(self::CounterPropertyName, 0);
        }
        set(int $value) {
            $this->logger->debug("{$this->character->id}: Set dragon counter value to {$value}.");
            $this->character->setProperty(self::CounterPropertyName, $value);
        }
    }

    /**
     * @var array<int, DragonPointChoice>
     */
    public array $choices {
        get {
            return $this->character->getProperty(self::ChoicePropertyName, []);
        }
        set(array $value) {
            $this->character->setProperty(self::ChoicePropertyName, $value);
        }
    }

    /**
     * @param string $choice
     * @param array<string, mixed> ...$kwargs
     * @return $this
     */
    public function addChoice(string $choice, array ... $kwargs): self
    {
        $choices = $this->choices;
        $choices[] = ["choice" => $choice, ... $kwargs];
        $this->choices = $choices;

        return $this;
    }
}