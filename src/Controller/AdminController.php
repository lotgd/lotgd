<?php
declare(strict_types=1);

namespace LotGD2\Controller;

use LotGD2\Repository\CharacterRepository;
use LotGD2\Twig\Component\Admin\Scenes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    #[Route("/admin/scene", "lotgd_admin_scene")]
    #[IsGranted("ROLE_SCENE_EDITOR")]
    public function sceneEditor(
        Security $security,
        CharacterRepository $characterRepository,
    ): Response {
        return $this->render("ucp/ucp.html.twig", [
            "component" => Scenes::class,
        ]);
    }
}