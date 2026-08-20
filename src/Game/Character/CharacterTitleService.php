<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Repository\TitleRepository;

class CharacterTitleService
{
    public function __construct(
        private TitleRepository $titleRepository,
        private DragonCounterHandler $dragonCounterHandler,
        private GenderHandler $genderHandler,
    ) {

    }

    public function setNextTitle(Character $character): void
    {
        $dragonKills = $this->dragonCounterHandler->getDragonCounter($character);
        $title = $this->titleRepository->getForDragonCounter($dragonKills);
        $gender = $this->genderHandler->getPreferredPronouns($character);

        if ($title) {
            $character->title = match($gender) {
                "male" => $title->male,
                "female" => $title->female,
                default => $title->other,
            };
        } else {
            $character->title = "Newbie";
        }
    }
}
