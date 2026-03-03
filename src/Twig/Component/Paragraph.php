<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph as ParagraphEntity;
use LotGD2\Game\GameLoop;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Paragraph
{
    public ParagraphEntity $paragraph;
    public Character $character;

    public function __construct(
        private GameLoop $game,
    ) {
        $this->character = $this->game->getCharacter();
    }
}