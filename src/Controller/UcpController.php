<?php
declare(strict_types=1);

namespace LotGD2\Controller;

use LotGD2\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UcpController extends AbstractController
{
    #[Route("/ucp/characters/list", name: "lotgd_ucp_list_characters")]
    #[IsGranted("ROLE_USER")]
    public function listCharacters(
        CharacterRepository $characterRepository,
    ): Response {
        return $this->render("ucp/list_characters.html.twig", [
            "characters" => $characterRepository->findAll(),
        ]);
    }
}