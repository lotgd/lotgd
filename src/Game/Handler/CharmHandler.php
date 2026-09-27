<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Handles the 'Charm' property.
 *
 * Charm is an integer stored in the charm field of a character's property field.
 *
 * Use [get|set|add]Charm to retrieve or control the amount of charm points of a given character.
 *
 * Listens to:
 *  - DragonTemplate::OnCharacterReset
 */
class CharmHandler
{
    const string PropertyName = 'charm';
    const string DragonCharmRewardParagraph = "lotgd2_paragraph_gold_DragonCharmReward";

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getCharm(Character $character): int
    {
        return (int)($character->getProperty(self::PropertyName, 0) ?? 0);
    }

    public function setCharm(Character $character, int $charm): void
    {
        $this->logger->debug("{$character}: Set new charm amount ({$charm}). Was {$this->getCharm($character)}.");
        $character->setProperty(self::PropertyName, $charm);
    }

    public function addCharm(Character $character, int $charm): void
    {
        $this->logger->debug("{$character}: Add {$charm} charm. Was {$this->getCharm($character)}.");
        $character->setProperty(self::PropertyName, $this->getCharm($character) + $charm);
    }

    #[AsEventListener(DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $event->stage->addParagraph(new Paragraph(
            id: self::DragonCharmRewardParagraph,
            text: "You gain FIVE charm points for having defeated the dragon!",
        ));

        $this->addCharm($event->character, 5);
    }
}