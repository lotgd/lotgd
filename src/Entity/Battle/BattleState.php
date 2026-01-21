<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Game\Character\Health;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Keeps track of the state of a battle.
 */
class BattleState
{
    #[Ignore]
    private(set) ?Character $character = null {
        get => $this->character;
        set(Character|null $character) => $this->character = $character;
    }

    /**
     * The current around of the battle.
     * @var int
     */
    public int $roundCounter = 0;

    /**
     * Contains a log of all rounds and the corresponding messages.
     * @var array<int, BattleRoundMessage>
     */
    public array $messageRounds = [];

    /**
     * The current status of the battle. Undecided by default.
     * @var BattleStateStatusEnum
     */
    public BattleStateStatusEnum $result = BattleStateStatusEnum::Undecided;


    /**
     * @param FighterInterface $goodGuy The good guy
     * @param FighterInterface $badGuy The bad guy
     * @param bool $isLevelAdjustmentEnabled Flag to enable or disable level adjustments
     * @param bool $isCriticalHitEnabled Flag to enable or disable critical hits
     * @param bool $isRiposteEnabled Flag to enable or disable ripostes (negative damage is turned into a counter-attack)
     */
    public function __construct(
        readonly private(set) FighterInterface $goodGuy,
        readonly private(set) FighterInterface $badGuy,
        readonly private(set) bool $isLevelAdjustmentEnabled = true,
        readonly private(set) bool $isCriticalHitEnabled = true,
        readonly private(set) bool $isRiposteEnabled = true,
        readonly private(set) bool $allowFlee = true,
    ) {
    }

    /**
     * Sets a reference to the current character
     * @param Character $character
     * @return void
     */
    public function setCharacter(Character $character): void
    {
        $this->character = $character;
    }

    /**
     * Call to synchronize the damage with the current character
     * @return void
     */
    public function syncronizeToCharacter(): void
    {
        if ($this->character === null) {
            throw new \LogicException("You must set the character first before synchronizing");
        }

        if ($this->goodGuy instanceof CurrentCharacterFighter) {
            $health = new Health(null, $this->character);
            $health->setHealth($this->goodGuy->health);
        }
    }

    /**
     * Increments the number of rounds by 1
     * @return void
     */
    public function incrementRound(): void
    {
        $this->roundCounter++;
    }

    /**
     * Adds battle messages to a new BattleRoundMessage container
     * @param iterable<int, BattleMessage> $messages
     * @return void
     */
    public function addMessages(iterable $messages): void
    {
        $messageRound = new BattleRoundMessage($this->roundCounter);
        foreach ($messages as $message) {
            $messageRound->add($message);
        }

        $this->messageRounds[] = $messageRound;
    }

    /**
     * Returns true if the fight is over.
     * @return bool
     */
    public function isOver(): bool
    {
        return $this->result !== BattleStateStatusEnum::Undecided;
    }
}