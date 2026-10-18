<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Mapped\Title;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Character\CharacterTitleService;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\GenderHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Repository\TitleRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CharacterTitleService::class)]
#[UsesClass(Character::class)]
#[UsesClass(Title::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(Stage::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(HandlerRegistry::class)]
class CharacterTitleServiceTest extends TestCase
{
    private function createService(
        ?TitleRepository $titleRepository = null,
        ?DragonCounterHandler $dragonCounterHandler = null,
        ?GenderHandler $genderHandler = null,
    ): CharacterTitleService {
        $handlerRegistry = new HandlerRegistry();
        if ($dragonCounterHandler !== null) {
            $handlerRegistry->addHandler(DragonCounterHandler::class, $dragonCounterHandler);
        }
        if ($genderHandler !== null) {
            $handlerRegistry->addHandler(GenderHandler::class, $genderHandler);
        }

        return new CharacterTitleService(
            $titleRepository ?? $this->createStub(TitleRepository::class),
            $handlerRegistry,
        );
    }

    public function testGetCompleteName(): void
    {
        $service = $this->createService();

        $character = new Character();
        $character->title = "Sir";
        $character->name = "Arthur";
        $character->suffix = "Pendragon";

        $this->assertSame("Sir Arthur Pendragon", $service->getCompleteName($character));

        $character->title = null;
        $character->suffix = null;
        $this->assertSame("Arthur", $service->getCompleteName($character));
    }

    #[TestWith(["male", "Sir"])]
    #[TestWith(["female", "Lady"])]
    #[TestWith(["other", "Noble"])]
    #[TestWith(["they", "Noble"])]
    public function testSetNextTitleWhenTitleExists(string $gender, string $expectedTitle): void
    {
        $character = new Character();
        $title = new Title(dk: 1, male: "Sir", female: "Lady", other: "Noble");

        $dragonCounterHandler = $this->createMock(DragonCounterHandler::class);
        $dragonCounterHandler->expects($this->once())
            ->method("getDragonCounter")
            ->with($character)
            ->willReturn(1);

        $titleRepository = $this->createMock(TitleRepository::class);
        $titleRepository->expects($this->once())
            ->method("getForDragonCounter")
            ->with(1)
            ->willReturn($title);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())
            ->method("getPreferredPronouns")
            ->with($character)
            ->willReturn($gender);

        $service = $this->createService($titleRepository, $dragonCounterHandler, $genderHandler);
        $service->setNextTitle($character);

        $this->assertSame($expectedTitle, $character->title);
    }

    public function testSetNextTitleWhenTitleNotFound(): void
    {
        $character = new Character();

        $dragonCounterHandler = $this->createMock(DragonCounterHandler::class);
        $dragonCounterHandler->expects($this->once())
            ->method("getDragonCounter")
            ->with($character)
            ->willReturn(0);

        $titleRepository = $this->createMock(TitleRepository::class);
        $titleRepository->expects($this->once())
            ->method("getForDragonCounter")
            ->with(0)
            ->willReturn(null);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())
            ->method("getPreferredPronouns")
            ->with($character)
            ->willReturn("male");

        $service = $this->createService($titleRepository, $dragonCounterHandler, $genderHandler);
        $service->setNextTitle($character);

        $this->assertSame("Newbie", $character->title);
    }

    public function testOnDragonKillWhenTitleChanges(): void
    {
        $character = new Character();
        $character->name = "Arthur";
        $character->title = "Newbie";

        $characterBefore = new Character();
        $characterBefore->name = "Arthur";
        $characterBefore->title = "Newbie";

        $stage = new Stage();

        $title = new Title(dk: 1, male: "Sir", female: "Lady", other: "Noble");

        $dragonCounterHandler = $this->createMock(DragonCounterHandler::class);
        $dragonCounterHandler->expects($this->once())
            ->method("getDragonCounter")
            ->with($character)
            ->willReturn(1);

        $titleRepository = $this->createMock(TitleRepository::class);
        $titleRepository->expects($this->once())
            ->method("getForDragonCounter")
            ->with(1)
            ->willReturn($title);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())
            ->method("getPreferredPronouns")
            ->with($character)
            ->willReturn("male");

        $event = new CharacterChangeEvent($character, $characterBefore, $stage);

        $service = $this->createService($titleRepository, $dragonCounterHandler, $genderHandler);
        $service->onDragonKill($event);

        $this->assertSame("Sir", $character->title);
        $this->assertCount(1, $stage->paragraphs);
        $this->assertArrayHasKey(CharacterTitleService::DragonKillTitleChangeParagraph, $stage->paragraphs);
        $paragraph = $stage->paragraphs[CharacterTitleService::DragonKillTitleChangeParagraph];
        $this->assertSame(CharacterTitleService::DragonKillTitleChangeParagraph, $paragraph->id);
        $this->assertSame(["fullName" => "Sir Arthur"], $paragraph->context);
    }

    public function testOnDragonKillWhenTitleDoesNotChange(): void
    {
        $character = new Character();
        $character->name = "Arthur";
        $character->title = "Sir";

        $characterBefore = new Character();
        $characterBefore->name = "Arthur";
        $characterBefore->title = "Sir";

        $stage = new Stage();

        $title = new Title(dk: 1, male: "Sir", female: "Lady", other: "Noble");

        $dragonCounterHandler = $this->createMock(DragonCounterHandler::class);
        $dragonCounterHandler->expects($this->once())
            ->method("getDragonCounter")
            ->with($character)
            ->willReturn(1);

        $titleRepository = $this->createMock(TitleRepository::class);
        $titleRepository->expects($this->once())
            ->method("getForDragonCounter")
            ->with(1)
            ->willReturn($title);

        $genderHandler = $this->createMock(GenderHandler::class);
        $genderHandler->expects($this->once())
            ->method("getPreferredPronouns")
            ->with($character)
            ->willReturn("male");

        $event = new CharacterChangeEvent($character, $characterBefore, $stage);

        $service = $this->createService($titleRepository, $dragonCounterHandler, $genderHandler);
        $service->onDragonKill($event);

        $this->assertSame("Sir", $character->title);
        $this->assertCount(0, $stage->paragraphs);
    }
}
