<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live\UCP;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
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

    const string CharacterSlotGameSetting = "lotgd2.core.characters.total_slots";

    #[LiveProp]
    public ?Character $character = null;

    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly GameStateService $gameState,
    ) {

    }

    /**
     * @return array<int, Character>
     */
    #[ExposeInTemplate]
    public function getCharacters(): array
    {
        return $this->characterRepository->findAll();
    }

    #[ExposeInTemplate]
    public function getCharacterSlots(): int
    {
        return $this->gameState->getSetting(self::CharacterSlotGameSetting) ?? 8;
    }

    #[ExposeInTemplate]
    public function getAvailableSlots(): int
    {
        return $this->getCharacterSlots() - $this->characterRepository->countOwnedCharacters();
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
