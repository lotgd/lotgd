<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Game\Handler\CharmHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CharmHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(Stage::class)]
class CharmHandlerTest extends TestCase
{
    private function createCharmHandler(?LoggerInterface $logger = null): CharmHandler
    {
        $logger ??= $this->createStub(LoggerInterface::class);

        return new CharmHandler($logger);
    }

    private function createCharacter(?string $name = "Hero", ?int $charm = null, ?int $id = null): Character
    {
        $character = new Character(name: $name);
        if ($charm !== null) {
            $character->setProperty(CharmHandler::PropertyName, $charm);
        }
        if ($id !== null) {
            $reflection = new \ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }

        return $character;
    }

    public function testGetCharmReturnsZeroWhenPropertyNotSet(): void
    {
        $character = $this->createCharacter();
        $handler = $this->createCharmHandler();

        $this->assertSame(0, $handler->getCharm($character));
    }

    public function testGetCharmReturnsZeroWhenPropertyIsNull(): void
    {
        $character = $this->createCharacter();
        $character->setProperty(CharmHandler::PropertyName, null);
        $handler = $this->createCharmHandler();

        $this->assertSame(0, $handler->getCharm($character));
    }

    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([15])]
    #[TestWith([100])]
    #[TestWith([-5])]
    public function testGetCharmReturnsConfiguredAmount(int $charmAmount): void
    {
        $character = $this->createCharacter(charm: $charmAmount);
        $handler = $this->createCharmHandler();

        $this->assertSame($charmAmount, $handler->getCharm($character));
    }

    #[TestWith([null, 10, 0, 10])]
    #[TestWith([5, 20, 5, 20])]
    #[TestWith([10, 0, 10, 0])]
    #[TestWith([5, -3, 5, -3])]
    public function testSetCharmLogsAndUpdatesProperty(
        ?int $initialCharm,
        int $newCharm,
        int $expectedOldCharm,
        int $expectedFinalCharm,
    ): void {
        $character = $this->createCharacter(name: "Arthur", charm: $initialCharm, id: 42);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#42, Arthur>: Set new charm amount ({$newCharm}). Was {$expectedOldCharm}.");

        $handler = $this->createCharmHandler($logger);
        $handler->setCharm($character, $newCharm);

        $this->assertSame($expectedFinalCharm, $handler->getCharm($character));
        $this->assertSame($expectedFinalCharm, $character->getProperty(CharmHandler::PropertyName));
    }

    #[TestWith([null, 5, 0, 5])]
    #[TestWith([10, 5, 10, 15])]
    #[TestWith([7, 0, 7, 7])]
    #[TestWith([10, -4, 10, 6])]
    #[TestWith([2, -5, 2, -3])]
    public function testAddCharmLogsAndUpdatesProperty(
        ?int $initialCharm,
        int $charmToAdd,
        int $expectedOldCharm,
        int $expectedFinalCharm,
    ): void {
        $character = $this->createCharacter(name: "Galahad", charm: $initialCharm, id: 99);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#99, Galahad>: Add {$charmToAdd} charm. Was {$expectedOldCharm}.");

        $handler = $this->createCharmHandler($logger);
        $handler->addCharm($character, $charmToAdd);

        $this->assertSame($expectedFinalCharm, $handler->getCharm($character));
        $this->assertSame($expectedFinalCharm, $character->getProperty(CharmHandler::PropertyName));
    }

    #[TestWith([null, 5])]
    #[TestWith([0, 5])]
    #[TestWith([10, 15])]
    public function testOnCharacterResetAddsParagraphAndAwardsFiveCharm(?int $initialCharm, int $expectedCharm): void
    {
        $character = $this->createCharacter(id: 1, name: "DragonSlayer", charm: $initialCharm);
        $characterBefore = $this->createCharacter(id: 1, name: "DragonSlayer", charm: $initialCharm);
        $stage = new Stage(owner: $character);

        $event = new CharacterChangeEvent(
            character: $character,
            characterBefore: $characterBefore,
            stage: $stage,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#1, DragonSlayer>: Add 5 charm. Was " . ($initialCharm ?? 0) . ".");

        $handler = $this->createCharmHandler($logger);
        $handler->onCharacterReset($event);

        $this->assertArrayHasKey(CharmHandler::DragonCharmRewardParagraph, $stage->paragraphs);
        $paragraph = $stage->paragraphs[CharmHandler::DragonCharmRewardParagraph];
        $this->assertInstanceOf(Paragraph::class, $paragraph);
        $this->assertSame(CharmHandler::DragonCharmRewardParagraph, $paragraph->id);
        $this->assertSame("You gain FIVE charm points for having defeated the dragon!", $paragraph->text);

        $this->assertSame($expectedCharm, $handler->getCharm($character));
        $this->assertSame($expectedCharm, $character->getProperty(CharmHandler::PropertyName));
    }
}
