<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Game\GameStateService;
use LotGD2\Repository\CharacterRepository;
use Symfony\Bundle\SecurityBundle\Security;

class CharacterService
{
    const string CharacterSlotGameSetting = "lotgd2.core.characters.total_slots";

    public function __construct(
        private readonly Security $security,
        private readonly CharacterRepository $characterRepository,
        private readonly GameStateService $gameStateService,
    ) {

    }

    public function getAllCharacters(

    ): array {

    }

    public function getTotalSlots(): int
    {
        return $this->gameStateService->getSetting(self::CharacterSlotGameSetting) ?? 8;
    }

    public function getAvailableSlots(): int
    {
        return $this->getTotalSlots() - $this->getUsedSlots();
    }

    public function getUsedSlots(): int
    {
        return $this->characterRepository->countOwnedCharacters();
    }
}
