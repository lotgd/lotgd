<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\NewEntityEvent;
use LotGD2\Game\GameStateService;
use LotGD2\Repository\CharacterRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CharacterService
{
    const string CharacterSlotGameSetting = "lotgd2.core.characters.total_slots";
    const string CharacterFormExtensionEventName = "lotgd2_character_FormExtension";
    const string NewCharacterEventName = "lotgd2_character_new";

    public function __construct(
        /** @phpstan-ignore property.onlyWritten */
        private readonly Security $security,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CharacterRepository $characterRepository,
        private readonly GameStateService $gameStateService,
        private readonly CharacterTitleService $titleService,
    ) {

    }

    /**
     * @return Character[]
     */
    public function getAllCharacters(

    ): array {
        return $this->characterRepository->findAll();
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

    public function newCharacter(Character $character): void
    {
        $this->titleService->setNextTitle($character);

        $event = new NewEntityEvent($character);
        $this->eventDispatcher->dispatch($event, self::NewCharacterEventName);
    }
}
