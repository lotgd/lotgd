<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Character\LootBag;
use LotGD2\Entity\Character\LootPosition;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Event\LootBagEvent;
use LotGD2\Event\NewEntityEvent;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormBuilderInterface;

#[CoversClass(GoldHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(LootPosition::class)]
#[UsesClass(LootBagEvent::class)]
#[UsesClass(LootBag::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(DiceBag::class)]
#[UsesClass(FormExtensionEvent::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(NewEntityEvent::class)]
class GoldHandlerTest extends TestCase
{
    public function testGetGoldWithPropertyNotSetAndGameSettingIsNull(): void
    {
        $character = new Character();
        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createStub(LoggerInterface::class);

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $this->assertEquals(50, $gold->getGold(null));
    }

    public static function getGoldProvider(): array
    {
        return [
            "no gold" => [0],
            "a bit gold" => [100],
            "a lot of gold" => [100_000_000],
        ];
    }

    #[DataProvider("getGoldProvider")]
    public function testGetGold(int $goldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $goldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createStub(LoggerInterface::class);

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $this->assertEquals($goldAmount, $gold->getGold(null));
    }

    public static function setGoldProvider(): array
    {
        return [
            [0, 100],
            [100, 0],
            [0, -100],
        ];
    }

    #[DataProvider("setGoldProvider")]
    public function testSetGold(int $initialGoldAmount, int $setGoldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $initialGoldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $gold->setGold(null, $setGoldAmount);
        $this->assertEquals($setGoldAmount, $gold->getGold(null));
    }

    public static function addGoldProvider(): array
    {
        return [
            [0, 100, 100],
            [100, 0, 100],
            [50, -100, -50],
        ];
    }

    #[DataProvider("addGoldProvider")]
    public function testAddGold(int $initialGoldAmount, int $setGoldAmount, int $finalGoldAmount): void
    {
        $character = new Character();
        $character->properties = [
            GoldHandler::PropertyName => $initialGoldAmount,
        ];

        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method("debug");

        $gold = new GoldHandler($gameStateService, $loggerMock, $character);

        $gold->addGold(null, $setGoldAmount);
        $this->assertEquals($finalGoldAmount, $gold->getGold(null));
    }

    #[TestWith([100, 100])]
    #[TestWith([1000, 1000])]
    #[TestWith([null, 1])]
    public function testOnLootBagFillAddsGoldReward(
        mixed $badGuyGoldAmount,
        int $maxValueExpected,
    ): void {
        $gameStateService = $this->createStub(GameStateService::class);
        $loggerMock = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = new GoldHandler($gameStateService, $loggerMock, $character);

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("kwargs"))->willReturn([
            "gold" => $badGuyGoldAmount,
        ]);

        $lootBag = $this->createMock(LootBag::class);

        $lootBagEvent = $this->getLootBagEvent(
            character: $character,
            badGuy: $badGuy,
            lootBag: $lootBag,
        );

        $lootBag->expects($this->once())
            ->method("add")
            ->willReturnCallback(function (LootPosition $position) use ($maxValueExpected) {
               $this->assertSame(GoldHandler::GoldLoot, $position->id);
               $this->assertSame(0, $position->loot["minValue"]);
               $this->assertSame($maxValueExpected, $position->loot["maxValue"]);
            });

        $goldHandler->onLootBagFill($lootBagEvent);
    }

    public function testOnLootBagClaimIfGoldLootBagIsNotFilledWithGold()
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createMock(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = new GoldHandler($gameStateService, $logger, $character);

        $lootBag = $this->createMock(LootBag::class);

        $lootBagEvent = $this->getLootBagEvent(
            character: $character,
            lootBag: $lootBag,
        );

        // Set assertions
        $lootBag->expects($this->once())
            ->method("get")
            ->with(GoldHandler::GoldLoot)
            ->willReturn(null);

        $logger->expects($this->once())
            ->method("debug")
            ->with("Impossible to claim gold loot: No gold loot exists.");

        $goldHandler->onLootBagClaim($lootBagEvent);
    }

    public function testOnLootBagClaimIfGoldLootBagIsIsProperlySet()
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = $this->getMockBuilder(GoldHandler::class)
            ->setConstructorArgs([$gameStateService, $logger, $character])
            ->onlyMethods(["addGold"])
            ->getMock();

        $diceBag = $this->createMock(DiceBag::class);

        $lootBag = $this->getMockBuilder(LootBag::class)
            ->setConstructorArgs([true, $diceBag])
            ->getMock();

        $stage = $this->createMock(Stage::class);

        $lootBagEvent = $this->getLootBagEvent(
            character: $character,
            lootBag: $lootBag,
            stage: $stage,
        );

        $lootPosition = new LootPosition(
            id: GoldHandler::GoldLoot,
            loot: ["minValue" => 0, "maxValue" => 100],
        );

        // Set assertions
        $lootBag->expects($this->once())
            ->method("get")
            ->with(GoldHandler::GoldLoot)
            ->willReturn($lootPosition);

        $diceBag->expects($this->once())
            ->method("pseudoBell")
            ->with(0, 100)
            ->willReturn(13);

        $goldHandler->expects($this->once())
            ->method("addGold")
            ->with($character, 13);

        $stage->expects($this->once())
            ->method("addParagraph")
            ->willReturnCallback(function (Paragraph $paragraph) use ($stage) {
                $this->assertSame(GoldHandler::GoldLootClaimParagraph, $paragraph->id);
                $this->assertSame(13, $paragraph->context["gold"]);
                return $stage;
            });

        $goldHandler->onLootBagClaim($lootBagEvent);
    }

    public function testOnLootBagClaimIfGoldLootBagIsIsImproperlySet()
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createMock(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = $this->getMockBuilder(GoldHandler::class)
            ->setConstructorArgs([$gameStateService, $logger, $character])
            ->onlyMethods(["addGold"])
            ->getMock();

        $diceBag = $this->createMock(DiceBag::class);

        $lootBag = $this->getMockBuilder(LootBag::class)
            ->setConstructorArgs([true, $diceBag])
            ->getMock();

        $stage = $this->createMock(Stage::class);

        $lootBagEvent = $this->getLootBagEvent(
            character: $character,
            lootBag: $lootBag,
            stage: $stage,
        );

        $lootPosition = new LootPosition(
            id: GoldHandler::GoldLoot,
            loot: [],
        );

        // Set assertions
        $lootBag->expects($this->once())
            ->method("get")
            ->with(GoldHandler::GoldLoot)
            ->willReturn($lootPosition);

        $diceBag->expects($this->once())
            ->method("pseudoBell")
            ->with(0, 0)
            ->willReturn(0);

        $goldHandler->expects($this->never())
            ->method("addGold");

        $stage->expects($this->never())
            ->method("addParagraph");

        $logger->expects($this->exactly(3))
            ->method("debug");

        $goldHandler->onLootBagClaim($lootBagEvent);
    }

    private function getLootBagEvent(
        Character $character,
        ?FighterInterface $goodGuy = null,
        ?FighterInterface $badGuy = null,
        ?LootBag $lootBag = null,
        ?Stage $stage = null
    ): LootBagEvent {
        $goodGuy ??= $this->createStub(FighterInterface::class);
        $badGuy ??= $this->createStub(FighterInterface::class);
        $lootBag ??= $this->createStub(LootBag::class);
        $stage ??= $this->createStub(Stage::class);

        /** @var BattleState&Stub $battleState */
        $battleState = $this->getStubBuilder(BattleState::class)
            ->setConstructorArgs([
                $goodGuy, $badGuy,
            ])
            ->onlyMethods([])
            ->getStub()
        ;
        $battleState->setCharacter($character);

        /** @var LootBagEvent&Stub $lootBagEvent */
        $lootBagEvent = $this->getStubBuilder(LootBagEvent::class)
            ->setConstructorArgs([$battleState, $lootBag, $stage])
            ->getStub()
        ;

        return $lootBagEvent;
    }

    public function testIfOnCharacterEditAddsFields()
    {
        $gameStateService = $this->createStub(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = new GoldHandler($gameStateService, $logger, $character);

        $builder = $this->createMock(FormBuilderInterface::class);
        $innerBuilder = $this->createStub(FormBuilderInterface::class);

        $builder->expects($this->atLeastOnce())
            ->method('add')
            ->with($innerBuilder)
        ;

        $builder->expects($this->atLeastOnce())
            ->method("create")
            ->willReturnCallback(function ($name, $type, $options) use ($innerBuilder) {
                $this->assertSame(GoldHandler::PropertyName, $name);
                return $innerBuilder;
            })
        ;

        /** @var FormExtensionEvent&\PHPUnit\Framework\MockObject\Stub\Stub $event */
        $event = $this->getStubBuilder(FormExtensionEvent::class)
            ->setConstructorArgs([$builder, []])
            ->getStub();
        ;

        $goldHandler->onGameSettingsFormExtension($event);
    }


    #[TestWith([null, null, null, [], 0])]
    #[TestWith([true, 50, 300, [], 0])]
    #[TestWith([true, 50, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 1], 50])]
    #[TestWith([true, null, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 1], 50])]
    #[TestWith([true, 50, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 2], 100])]
    #[TestWith([true, 50, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 10], 300])]
    #[TestWith([false, 50, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 1], 50])]
    #[TestWith([false, 50, 300, [DragonTemplate::OnCharacterResetDragonCounterParameter => 10], 50])]
    public function testIfOnCharacterResetSetsStartGoldProperly(
        mixed $startGoldDoesScale,
        mixed $startGold,
        mixed $maxStartGold,
        array $eventParameters,
        int $expectedGold
    ) {
        $gameStateService = $this->createMock(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = $this->getMockBuilder(GoldHandler::class)
            ->setConstructorArgs([$gameStateService, $logger, $character])
            ->onlyMethods(["setGold"])
            ->getMock()
        ;

        /** @var CharacterChangeEvent&Stub $event */
        $event = $this->getStubBuilder(CharacterChangeEvent::class)
            ->setConstructorArgs([$character, $character, null, $eventParameters])
            ->getStub()
        ;

        // Expections
        $matcher = $this->exactly(3);
        $gameStateService
            ->expects($matcher)
            ->method("getSetting")
            ->willReturnCallback(function (string $name) use ($matcher, $startGold, $startGoldDoesScale, $maxStartGold) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(GoldHandler::StartGoldScalesWithDragonKillGameSetting, $name),
                    2 => $this->assertSame(GoldHandler::DefaultGoldGameSetting, $name),
                    3 => $this->assertSame(GoldHandler::MaxStartGoldGameSetting, $name),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $startGoldDoesScale,
                    2 => $startGold,
                    3 => $maxStartGold,
                };
            })
        ;

        $goldHandler->expects($this->once())
            ->method("setGold")
            ->with($character, $expectedGold)
        ;

        $goldHandler->onCharacterReset($event);
    }

    #[TestWith([null, 50])]
    #[TestWith([50, 50])]
    #[TestWith([100, 100])]
    public function testIfOnCharacterCreationSetsStartGoldProperly(
        mixed $startGold,
        int $expectedGold
    ) {
        $gameStateService = $this->createMock(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = $this->getMockBuilder(GoldHandler::class)
            ->setConstructorArgs([$gameStateService, $logger, $character])
            ->onlyMethods(["setGold"])
            ->getMock()
        ;

        /** @var CharacterChangeEvent&Stub $event */
        $event = $this->getStubBuilder(NewEntityEvent::class)
            ->setConstructorArgs([$character])
            ->getStub()
        ;

        // Expections
        $gameStateService
            ->expects($this->once())
            ->method("getSetting")
            ->with(GoldHandler::DefaultGoldGameSetting)
            ->willReturn($startGold)
        ;

        $goldHandler->expects($this->once())
            ->method("setGold")
            ->with($character, $expectedGold)
        ;

        $goldHandler->onCharacterCreation($event);
    }

    public function testIfOnCharacterCreationDoesNothingIfEntityIsNotCharacter()
    {
        $gameStateService = $this->createMock(GameStateService::class);
        $logger = $this->createStub(LoggerInterface::class);
        $character = $this->createStub(Character::class);

        $goldHandler = $this->getMockBuilder(GoldHandler::class)
            ->setConstructorArgs([$gameStateService, $logger, $character])
            ->onlyMethods(["setGold"])
            ->getMock()
        ;

        $a = new class {};

        /** @var NewEntityEvent&Stub $event */
        $event = $this->getStubBuilder(NewEntityEvent::class)
            ->setConstructorArgs([$a])
            ->getStub()
        ;

        // Expections
        $gameStateService
            ->expects($this->never())
            ->method("getSetting")
        ;

        $goldHandler->expects($this->never())
            ->method("setGold")
        ;

        $goldHandler->onCharacterCreation($event);
    }
}
