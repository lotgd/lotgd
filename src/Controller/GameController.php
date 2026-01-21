<?php
declare(strict_types=1);

namespace LotGD2\Controller;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Error\InvalidActionError;
use LotGD2\Game\GameLoop;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GameController extends AbstractController
{
    #[Route("/game/{character}/stage", "lotgd_game_stage")]
    #[IsGranted("ROLE_USER")]
    public function view(
        GameLoop $gameLoop,
        Character $character,
    ): Response {
        $gameLoop->setCharacter($character);

        return $this->render("game/view.html.twig", [
            "character" => $character,
        ]);
    }

    #[Route("/game/{character}/action/{action}", "lotgd_game_action")]
    #[IsGranted("ROLE_USER")]
    public function takeAction(
        GameLoop $gameLoop,
        Character $character,
        string $action,
    ): Response {
        try {
            $gameLoop->setCharacter($character);
            $stage = $gameLoop->takeAction($character, $action);
        } catch (InvalidActionError) {
            $stage = $character->stage;
        }

        return $this->render("game/view.html.twig", [
            "character" => $character,
            "stage" => $stage,
        ]);
    }
}