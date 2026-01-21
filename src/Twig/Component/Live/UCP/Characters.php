<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live\UCP;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Repository\CharacterRepository;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Characters
{
    use DefaultActionTrait;

    public function __construct(
        private readonly CharacterRepository $characterRepository,
    ) {

    }

    /**
     * @return array<int, Character>
     */
    public function getCharacters(): array
    {
        return $this->characterRepository->findAll();
    }
}