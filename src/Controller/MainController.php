<?php
declare(strict_types=1);

namespace LotGD2\Controller;

use LotGD2\Repository\CharacterRepository;
use LotGD2\Twig\Component\Live\UCP\Characters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route("/", "lotgd_main")]
    public function home(
        Security $security,
        CharacterRepository $characterRepository,
    ): Response {
        if ($security->isGranted("ROLE_USER")) {
            return $this->render("ucp/ucp.html.twig", [
                "component" => Characters::class,
            ]);
        } else {
            return $this->redirectToRoute("lotgd_login");
        }
    }
}