<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use LotGD2\Repository\TitleRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class CharacterTitleService
{
    const string DragonKillTitleChangeParagraph = "lotgd2.paragraph.TitleService.dragonKillTitleChange";

    public function __construct(
        private TitleRepository $titleRepository,
        private DragonCounterHandler $dragonCounterHandler,
        private GenderHandler $genderHandler,
    ) {

    }

    public function getCompleteName(Character $character): string
    {
        $name_fragments = [$character->title, $character->name, $character->suffix];
        $name_fragments = array_filter($name_fragments);

        return implode(" ", $name_fragments);
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

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onDragonKill(CharacterChangeEvent $event): void
    {
        $this->setNextTitle($event->character);

        if ($event->character->title === $event->characterBefore->title) {
            return;
        }

        $event->stage->addParagraph(
            new Paragraph(
                id: self::DragonKillTitleChangeParagraph,
                text: "You are now known as {{ fullName }}",
                context: [
                    "fullName" => $this->getCompleteName($event->character),
                ]
            )
        );
    }
}
