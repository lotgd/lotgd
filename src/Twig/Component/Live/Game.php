<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live;

use LotGD2\Entity\Character;
use LotGD2\Game\GameLoop;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Game extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Character $character;

    public function __construct(
        private readonly GameLoop $game,
    ) {

    }

    #[Expose]
    public function getStage()
    {
        return $this->game->getStage($this->character);
    }

    #[LiveAction]
    public function takeAction(
        #[LiveArg]
      Character $character,
        #[LiveArg]
      string $actionId,
    ) {
        $stage = $this->game->takeAction($character, $actionId);
    }
}
