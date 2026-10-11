<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Character;

use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Character\LootBag;
use LotGD2\Entity\Character\LootPosition;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\LootBagEvent;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBag;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(StatsHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(EquipmentItem::class)]
#[UsesClass(EquipmentHandler::class)]
#[UsesClass(LootPosition::class)]
#[UsesClass(LootBag::class)]
#[UsesClass(LootBagEvent::class)]
#[UsesClass(BattleState::class)]
#[UsesClass(Stage::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(CharacterChangeEvent::class)]
#[UsesClass(DiceBag::class)]
class StatsTest extends TestCase
{
    private function createCharacter(
        ?string $name = "Hero",
        ?int $id = 1,
        ?int $level = null,
        ?int $attack = null,
        ?int $defense = null,
        ?int $experience = null,
    ): Character {
        $character = new Character(name: $name);
        if ($id !== null) {
            $reflection = new \ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }
        if ($level !== null) {
            $character->level = $level;
        }
        if ($attack !== null) {
            $character->setProperty(StatsHandler::AttackPropertyName, $attack);
        }
        if ($defense !== null) {
            $character->setProperty(StatsHandler::DefensePropertyName, $defense);
        }
        if ($experience !== null) {
            $character->setProperty(StatsHandler::ExperiencePropertyName, $experience);
        }

        return $character;
    }

    private function createStatsHandler(
        ?LoggerInterface $logger = null,
        ?EquipmentHandler $equipment = null,
        ?Character $character = null,
    ): StatsHandler {
        return new StatsHandler(
            $logger ?? $this->createStub(LoggerInterface::class),
            $equipment ?? $this->createStub(EquipmentHandler::class),
            $character ?? $this->createCharacter(),
        );
    }

    public function testGetExperienceReturnsDefaultZeroWhenNotSet(): void
    {
        $character = $this->createCharacter();
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(0, $stats->getExperience());
    }

    public function testGetExperienceReturnsSetValue(): void
    {
        $expectedExperience = 1000;
        $character = $this->createCharacter(experience: $expectedExperience);
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals($expectedExperience, $stats->getExperience());
    }

    public function testSetExperienceCallsCharacterSetProperty(): void
    {
        $character = $this->createCharacter(id: 1, name: "Hero");
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#1, Hero>: set experience to 500");

        $stats = $this->createStatsHandler(logger: $logger, character: $character);
        $result = $stats->setExperience(500);

        $this->assertSame($stats, $result);
        $this->assertSame(500, $character->getProperty(StatsHandler::ExperiencePropertyName));
    }

    public function testSetExperienceReturnsStaticForChaining(): void
    {
        $stats = $this->createStatsHandler();
        $result = $stats->setExperience(100);

        $this->assertInstanceOf(StatsHandler::class, $result);
        $this->assertSame($stats, $result);
    }

    public function testAddExperienceAddsToCurrentValue(): void
    {
        $character = $this->createCharacter(experience: 100);
        $stats = $this->createStatsHandler(character: $character);

        $result = $stats->addExperience(50);

        $this->assertSame($stats, $result);
        $this->assertSame(150, $stats->getExperience());
    }

    public function testAddExperienceWithZeroCurrentValue(): void
    {
        $character = $this->createCharacter();
        $stats = $this->createStatsHandler(character: $character);

        $stats->addExperience(100);

        $this->assertSame(100, $stats->getExperience());
    }

    public function testAddExperienceReturnsStaticForChaining(): void
    {
        $stats = $this->createStatsHandler();
        $result = $stats->addExperience(50);

        $this->assertInstanceOf(StatsHandler::class, $result);
        $this->assertSame($stats, $result);
    }

    public function testGetLevelCallsCharacterGetLevel(): void
    {
        $character = $this->createCharacter(level: 5);
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(5, $stats->getLevel());
    }

    public function testGetAttackReturnsDefaultValueOne(): void
    {
        $character = $this->createCharacter();
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(1, $stats->getAttack());
    }

    public function testGetAttackReturnsSetValue(): void
    {
        $character = $this->createCharacter(attack: 10);
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(10, $stats->getAttack());
    }

    public function testSetAttackCallsCharacterSetProperty(): void
    {
        $attack = 15;
        $character = $this->createCharacter(id: 1, name: "Hero", attack: 10);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#1, Hero>: attack set to 15 (was 10) before).");

        $stats = $this->createStatsHandler(logger: $logger, character: $character);
        $result = $stats->setAttack($attack);

        $this->assertSame($stats, $result);
        $this->assertSame(15, $stats->getAttack());
    }

    public function testSetAttackWithNullLogger(): void
    {
        $character = $this->createCharacter();
        $equipment = $this->createStub(EquipmentHandler::class);
        $statsWithNullLogger = new StatsHandler(
            null,
            $equipment,
            $character
        );

        $attack = 15;
        $result = $statsWithNullLogger->setAttack($attack);

        $this->assertSame($statsWithNullLogger, $result);
        $this->assertSame(15, $statsWithNullLogger->getAttack());
    }

    public function testAddAttackAddsToCurrentValue(): void
    {
        $character = $this->createCharacter(attack: 10);
        $stats = $this->createStatsHandler(character: $character);

        $result = $stats->addAttack(5);

        $this->assertSame($stats, $result);
        $this->assertSame(15, $stats->getAttack());
    }

    public function testGetDefenseReturnsDefaultValueOne(): void
    {
        $character = $this->createCharacter();
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(1, $stats->getDefense());
    }

    public function testGetDefenseReturnsSetValue(): void
    {
        $character = $this->createCharacter(defense: 8);
        $stats = $this->createStatsHandler(character: $character);

        $this->assertEquals(8, $stats->getDefense());
    }

    public function testSetDefenseCallsCharacterSetProperty(): void
    {
        $defense = 12;
        $character = $this->createCharacter(id: 1, name: "Hero", defense: 8);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("<Character#1, Hero>: defense set to 12 (was 8) before).");

        $stats = $this->createStatsHandler(logger: $logger, character: $character);
        $result = $stats->setDefense($defense);

        $this->assertSame($stats, $result);
        $this->assertSame(12, $stats->getDefense());
    }

    public function testSetDefenseWithNullLogger(): void
    {
        $character = $this->createCharacter();
        $equipment = $this->createStub(EquipmentHandler::class);
        $statsWithNullLogger = new StatsHandler(
            null,
            $equipment,
            $character
        );

        $defense = 12;
        $result = $statsWithNullLogger->setDefense($defense);

        $this->assertSame($statsWithNullLogger, $result);
        $this->assertSame(12, $statsWithNullLogger->getDefense());
    }

    public function testAddDefenseAddsToCurrentValue(): void
    {
        $character = $this->createCharacter(defense: 8);
        $stats = $this->createStatsHandler(character: $character);

        $result = $stats->addDefense(3);

        $this->assertSame($stats, $result);
        $this->assertSame(11, $stats->getDefense());
    }

    public function testGetTotalAttackWithNoWeapon(): void
    {
        $character = $this->createCharacter(attack: 5);
        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::WeaponSlot, $character)
            ->willReturn(null);

        $stats = $this->createStatsHandler(equipment: $equipment, character: $character);

        $this->assertEquals(5, $stats->getTotalAttack());
    }

    public function testGetTotalAttackWithWeapon(): void
    {
        $character = $this->createCharacter(attack: 5);
        $weapon = new EquipmentItem("Sword", 10, 100);

        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::WeaponSlot, $character)
            ->willReturn($weapon);

        $stats = $this->createStatsHandler(equipment: $equipment, character: $character);

        $this->assertEquals(15, $stats->getTotalAttack());
    }

    public function testGetTotalDefenseWithNoArmor(): void
    {
        $character = $this->createCharacter(defense: 3);
        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::ArmorSlot, $character)
            ->willReturn(null);

        $stats = $this->createStatsHandler(equipment: $equipment, character: $character);

        $this->assertEquals(3, $stats->getTotalDefense());
    }

    public function testGetTotalDefenseWithArmor(): void
    {
        $character = $this->createCharacter(defense: 3);
        $armor = new EquipmentItem("Shield", 7, 100);

        $equipment = $this->createMock(EquipmentHandler::class);
        $equipment
            ->expects($this->once())
            ->method('getItemInSlot')
            ->with(EquipmentHandler::ArmorSlot, $character)
            ->willReturn($armor);

        $stats = $this->createStatsHandler(equipment: $equipment, character: $character);

        $this->assertEquals(10, $stats->getTotalDefense());
    }

    public function testMultipleExperienceAdditions(): void
    {
        $character = $this->createCharacter(experience: 100);
        $stats = $this->createStatsHandler(character: $character);

        $stats->addExperience(50);
        $this->assertSame(150, $stats->getExperience());
    }

    public function testConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals('experience', StatsHandler::ExperiencePropertyName);
        $this->assertEquals('level', StatsHandler::LevelPropertyName);
        $this->assertEquals('attack', StatsHandler::AttackPropertyName);
        $this->assertEquals('defense', StatsHandler::DefensePropertyName);
    }

    public function testRequiredExperienceAlwaysIncreases(): void
    {
        $character = $this->createCharacter();
        $stats = $this->createStatsHandler(character: $character);

        $required = 0;
        for ($level = 1; $level <= 15; $level++) {
            $character->level = $level;
            $requiredExperience = $stats->getRequiredExperience();
            $this->assertNotNull($requiredExperience);
            $this->assertGreaterThan($required, $requiredExperience);
            $required = $requiredExperience;
        }
    }

    public function testRequiredExperienceIsNullAboveMaxLevel(): void
    {
        $character = $this->createCharacter(level: 16);
        $stats = $this->createStatsHandler(character: $character);

        $this->assertNull($stats->getRequiredExperience());
    }

    public function testExplicitCharacterInStatsMethods(): void
    {
        $defaultCharacter = $this->createCharacter(defense: 0, experience: 0);
        $stats = $this->createStatsHandler(character: $defaultCharacter);

        $explicitCharacter = new Character();
        $stats->setExperience(500, $explicitCharacter);
        $this->assertSame(500, $stats->getExperience($explicitCharacter));

        $stats->addExperience(200, $explicitCharacter);
        $this->assertSame(200, $stats->getExperience($explicitCharacter));

        $stats->setAttack(12, $explicitCharacter);
        $this->assertSame(12, $stats->getAttack($explicitCharacter));

        $stats->addAttack(3, $explicitCharacter);
        $this->assertSame(15, $stats->getAttack($explicitCharacter));

        $stats->setDefense(8, $explicitCharacter);
        $this->assertSame(8, $stats->getDefense($explicitCharacter));

        $stats->addDefense(2, $explicitCharacter);
        $this->assertSame(2, $stats->getDefense($explicitCharacter));
    }

    public function testOnCharacterLevelIncrease(): void
    {
        $character = $this->createCharacter(level: 4);
        $characterBefore = $this->createCharacter(level: 2);

        $statsHandler = $this->createStatsHandler(character: $character);

        $event = new CharacterChangeEvent($character, $characterBefore);
        $statsHandler->onCharacterLevelIncrease($event);

        $this->assertSame(3, $statsHandler->getAttack($character)); // 1 base + 2
        $this->assertSame(3, $statsHandler->getDefense($character)); // 1 base + 2
    }

    public function testOnCharacterReset(): void
    {
        $character = $this->createCharacter(
            level: 1,
            attack: 10,
            defense: 10,
            experience: 5000,
        );

        $characterBefore = $this->createCharacter(level: 10);

        $statsHandler = $this->createStatsHandler(character: $character);

        $event = new CharacterChangeEvent($character, $characterBefore);
        $statsHandler->onCharacterReset($event);

        $this->assertSame(1, $statsHandler->getAttack($character));
        $this->assertSame(1, $statsHandler->getDefense($character));
        $this->assertSame(0, $statsHandler->getExperience($character));

        // When delta level <= 0
        $characterBefore2 = $this->createCharacter(level: 1);
        $event2 = new CharacterChangeEvent($character, $characterBefore2);

        $statsHandler->onCharacterReset($event2);
        $this->assertSame(1, $statsHandler->getAttack($character));
    }

    public function testOnLootBagFill(): void
    {
        $character = $this->createCharacter();
        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get("kwargs"))->willReturn([
            "experience" => 45,
        ]);

        $battleState = new BattleState($this->createStub(FighterInterface::class), $badGuy);
        $battleState->setCharacter($character);

        $lootBag = $this->createMock(LootBag::class);
        $lootBag->expects($this->once())
            ->method("add")
            ->willReturnCallback(function (LootPosition $pos) {
                $this->assertSame(StatsHandler::ExperienceLoot, $pos->id);
                $this->assertSame(45, $pos->loot["experience"]);
                $this->assertSame(0.1, $pos->loot["experienceFlux"]);
                $this->assertSame(0.25, $pos->loot["experienceBonus"]);
            });

        $event = new LootBagEvent($battleState, $lootBag);

        $statsHandler = $this->createStatsHandler(character: $character);
        $statsHandler->onLootBagFill($event);
    }

    public function testOnLootBagClaimWhenNoLootPosition(): void
    {
        $character = $this->createCharacter();
        $battleState = new BattleState(
            $this->createStub(FighterInterface::class),
            $this->createStub(FighterInterface::class),
        );
        $battleState->setCharacter($character);

        $lootBag = $this->createMock(LootBag::class);
        $lootBag->expects($this->once())
            ->method("get")
            ->with(StatsHandler::ExperienceLoot)
            ->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with("Impossible to claim experience loot: No experience loot exists.");

        $event = new LootBagEvent($battleState, $lootBag);

        $statsHandler = $this->createStatsHandler(logger: $logger, character: $character);
        $statsHandler->onLootBagClaim($event);
    }

    public function testOnLootBagClaimWithMissingKeys(): void
    {
        $character = $this->createCharacter(level: 3);

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get('level'))->willReturn(3);

        $battleState = new BattleState($this->createStub(FighterInterface::class), $badGuy);
        $battleState->setCharacter($character);

        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->once())
            ->method("pseudoBell")
            ->with(0, 0)
            ->willReturn(0);

        $lootBag = new LootBag(false, $diceBag);
        $position = new LootPosition(StatsHandler::ExperienceLoot, []);
        $lootBag->add($position);

        $stage = new Stage();
        $event = new LootBagEvent($battleState, $lootBag, $stage);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(5))->method("debug");

        $statsHandler = $this->createStatsHandler(logger: $logger, character: $character);
        $statsHandler->onLootBagClaim($event);

        $this->assertSame(1, $statsHandler->getExperience($character));
        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(1, $paragraphs);
        $this->assertSame(StatsHandler::ExperienceLootClaimParagraph, $paragraphs[0]->id);
    }

    public function testOnLootBagClaimWithPositiveBonusAndNegativeBonus(): void
    {
        $character = $this->createCharacter(level: 1);

        $badGuy = $this->createStub(FighterInterface::class);
        $badGuy->method(PropertyHook::get('level'))->willReturn(3); // stronger bad guy -> deltaLevel = +2

        $battleState = new BattleState($this->createStub(FighterInterface::class), $badGuy);
        $battleState->setCharacter($character);

        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->once())
            ->method("pseudoBell")
            ->with(-10, 10)
            ->willReturn(5);

        $lootBag = new LootBag(false, $diceBag);
        $position = new LootPosition(StatsHandler::ExperienceLoot, [
            "experience" => 100,
            "experienceFlux" => 0.1,
            "experienceBonus" => 0.25,
        ]);
        $lootBag->add($position);

        $stage = new Stage();
        $event = new LootBagEvent($battleState, $lootBag, $stage);

        $statsHandler = $this->createStatsHandler(character: $character);
        $statsHandler->onLootBagClaim($event);

        // 100 + 5 (flux) + (0.25 * 100 * 2 = 50) = 155
        $this->assertSame(155, $statsHandler->getExperience($character));
        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(1, $paragraphs);
        $this->assertSame(50.0, $paragraphs[0]->context["bonusExperience"]);
        $this->assertSame(155, $paragraphs[0]->context["experience"]);

        // Negative bonus: weaker bad guy -> deltaLevel = -2
        $character2 = $this->createCharacter(level: 5);
        $badGuy2 = $this->createStub(FighterInterface::class);
        $badGuy2->method(PropertyHook::get('level'))->willReturn(3);

        $battleState2 = new BattleState($this->createStub(FighterInterface::class), $badGuy2);
        $battleState2->setCharacter($character2);

        $diceBag2 = $this->createMock(DiceBag::class);
        $diceBag2->expects($this->once())
            ->method("pseudoBell")
            ->willReturn(0);

        $lootBag2 = new LootBag(false, $diceBag2);
        $lootBag2->add(new LootPosition(StatsHandler::ExperienceLoot, [
            "experience" => 100,
            "experienceFlux" => 0.1,
            "experienceBonus" => 0.25,
        ]));

        $stage2 = new Stage();
        $event2 = new LootBagEvent($battleState2, $lootBag2, $stage2);

        $statsHandler2 = $this->createStatsHandler(character: $character2);
        $statsHandler2->onLootBagClaim($event2);

        // 100 + 0 - 50 = 50
        $this->assertSame(50, $statsHandler2->getExperience($character2));
        $paragraphs2 = array_values($stage2->paragraphs);
        $this->assertSame(-50.0, $paragraphs2[0]->context["bonusExperience"]);
    }
}
