<?php
declare(strict_types=1);

namespace LotGD2\Controller;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Character;
use LotGD2\Game\Error\InvalidActionError;
use LotGD2\Game\GameLoop;
use LotGD2\Game\Scene\SceneRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GameController extends AbstractController
{
    #[Route("/game/{character}/stage", "lotgd_game_stage")]
    #[IsGranted("ROLE_USER")]
    public function view(
        Character $character,
    ): Response {
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
            $newStage = $gameLoop->takeAction($character, $action);
        } catch (InvalidActionError $error) {
        }

        return $this->render("game/view.html.twig", [
            "character" => $character,
            "stage" => $newStage,
        ]);
    }
}