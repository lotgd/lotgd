<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live\UCP;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Character\CharacterService;
use LotGD2\Game\GameStateService;
use LotGD2\Repository\CharacterRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class Characters
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Character $character = null;

    public function __construct(
        private readonly CharacterService $characterService,
    ) {

    }

    #[ExposeInTemplate]
    public function getCharacterService(): CharacterService
    {
        return $this->characterService;
    }

    /**
     * @return array<int, Character>
     */
    #[ExposeInTemplate]
    public function getCharacters(): array
    {
        return $this->characterService->getAllCharacters();
    }

    #[LiveAction]
    public function showForm(
    ): void {
        $this->character = null;
    }

    #[LiveListener('characterAdded')]
    public function onCharacterAdded(
        #[LiveArg]
        Character $character,
    ): void {
        $this->character = $character;
    }
}
