<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Character\LootPosition;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\LootBagEvent;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class Gold
{
    const string PropertyName = 'gold';
    const string GoldLoot = "lotgd2.loot.Gold";
    const string GoldLootClaimParagraph = "lotgd2.paragraph.Gold.LootBagClaim";

    public function __construct(
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function getGold(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(self::PropertyName, 0);
    }

    public function setGold(?Character $character, int $gold): static
    {
        $character = $character ?? $this->character;
        $this->logger->debug("{$character} set new gold amount ($gold). Was {$this->getGold($character)}.");

        $character->setProperty(self::PropertyName, $gold);
        return $this;
    }

    public function addGold(?Character $character, int $gold): static
    {
        $character = $character ?? $this->character;
        $newGoldAmount = $this->getGold($character) + $gold;
        $this->logger->debug("{$character} add gold ($gold). Was {$this->getGold($character)}, is now {$newGoldAmount}");

        $character->setProperty(self::PropertyName, $newGoldAmount);
        return $this;
    }

    #[AsEventListener(FightTemplate::OnLootBagFill)]
    public function onLootBagFill(LootBagEvent $event): void
    {
        $event->lootBag->add(new LootPosition(self::GoldLoot, [
            "minValue" => 0,
            "maxValue" => $event->battleState->badGuy->kwargs["gold"] ?? 1,
        ]));
    }

    #[AsEventListener(FightTemplate::OnLootBagClaim)]
    public function onLootBagClaim(LootBagEvent $event): void
    {
        $lootBag = $event->lootBag;
        $position = $lootBag->get(self::GoldLoot);

        if ($position === null) {
            $this->logger->debug("Impossible to claim gold loot: No gold loot exists.");
            return;
        }

        if (!isset($position->loot["minValue"])) {
            $this->logger->debug("There is no minValue on GoldLoot position. It was probably removed accidentally.");
        }

        if (!isset($position->loot["maxValue"])) {
            $this->logger->debug("There is no minValue on GoldLoot position. It was probably removed accidentally.");
        }

        $goldReward = $lootBag->diceBag->pseudoBell($position->loot["minValue"] ?? 0, $position->loot["maxValue"] ?? 0);
        $this->addGold($event->character, $goldReward);

        $event->stage?->addParagraph(new Paragraph(
            self::GoldLootClaimParagraph,
            text: <<<TXT
                    You earn {{ gold }} gold.
                    TXT,
            context: [
                "gold" => $goldReward,
            ]
        ));
    }
}