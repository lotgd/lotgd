<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameLoop;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Gold
{
    const string PropertyName = 'gold';

    public function __construct(
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getGold(): int
    {
        return $this->character->getProperty(self::PropertyName, 0);
    }

    public function setGold(int $gold): static
    {
        $this->logger->debug("{$this->character->id} set new gold amount ($gold). Was {$this->getGold()}.");

        $this->character->setProperty(self::PropertyName, $gold);
        return $this;
    }

    public function addGold(int $gold): static
    {
        $newGoldAmount = $this->getGold() + $gold;
        $this->logger->debug("{$this->character->id} add gold ($gold). Was {$this->getGold()}, is now {$newGoldAmount}");

        $this->character->setProperty(self::PropertyName, $newGoldAmount);
        return $this;
    }
}