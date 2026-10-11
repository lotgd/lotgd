<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use LotGD2\Entity\Action;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(BuffHandler::class)]
#[UsesClass(Buff::class)]
#[UsesClass(BuffList::class)]
#[UsesClass(Character::class)]
#[UsesClass(ProtoBuff::class)]
#[UsesClass(Paragraph::class)]
#[UsesClass(StageChangeEvent::class)]
#[UsesClass(Stage::class)]
#[UsesClass(Action::class)]
#[UsesClass(Scene::class)]
#[UsesClass(DiceBag::class)]
class BuffHandlerTest extends TestCase
{
    private function createCharacter(
        ?string $name = "Hero",
        ?int $id = 123,
        ?array $buffs = null,
    ): Character {
        $character = new Character(name: $name);
        if ($id !== null) {
            $reflection = new \ReflectionProperty(Character::class, "id");
            $reflection->setValue($character, $id);
        }
        if ($buffs !== null) {
            $character->setProperty(BuffHandler::BuffPropertyName, $buffs);
        }

        return $character;
    }

    private function createBuffHandler(
        ?LoggerInterface $logger = null,
        ?DiceBagInterface $diceBag = null,
        ?ExpressionService $expressionService = null,
    ): BuffHandler {
        return new BuffHandler(
            $logger ?? $this->createStub(LoggerInterface::class),
            $diceBag ?? $this->createStub(DiceBagInterface::class),
            $expressionService ?? $this->createStub(ExpressionService::class),
        );
    }

    private function createDummyFighter(?array $buffs = null): FighterInterface
    {
        $fighter = new class implements FighterInterface {
            public ?int $maxHealth = 100;
            public array $kwargs = [];
            public ?int $id = null;
            public ?string $name = "Dummy";
            public ?int $health = 100;
            public ?int $level = 1;
            public ?string $weapon = "Stick";
            public ?int $attack = 1;
            public ?int $defense = 1;

            public function __construct(
                ?string $name = "Dummy",
                ?int $level = 1,
                ?string $weapon = "Stick",
                ?int $health = 100,
                ?int $attack = 1,
                ?int $defense = 1,
            ) {
                $this->name = $name;
                $this->level = $level;
                $this->weapon = $weapon;
                $this->health = $health;
                $this->attack = $attack;
                $this->defense = $defense;
            }

            public function damage(int $damage): static
            {
                return $this;
            }
        };

        if ($buffs !== null) {
            $fighter->kwargs[BuffHandler::BuffPropertyName] = $buffs;
        }

        return $fighter;
    }

    public function testGetBuffListForCharacter(): void
    {
        $buff = new Buff(id: "buff_1", name: "Blessing", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);
        $character = $this->createCharacter(buffs: [$buff]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method("debug")
            ->with($this->stringContains("Returns new BuffList"));

        $handler = $this->createBuffHandler(logger: $logger);
        $buffList = $handler->getBuffList($character);

        $this->assertInstanceOf(BuffList::class, $buffList);
        $this->assertSame([$buff], $buffList->buffs);
    }

    public function testGetBuffListForNonCharacterFighterWithNullDependencies(): void
    {
        $buff = new Buff(id: "buff_2", name: "Curse", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);
        $fighter = $this->createDummyFighter(buffs: [$buff]);

        $handler = new BuffHandler(null, null, $this->createStub(ExpressionService::class));
        $buffList = $handler->getBuffList($fighter);

        $this->assertInstanceOf(BuffList::class, $buffList);
        $this->assertSame([$buff], $buffList->buffs);
    }

    public function testGetBuffsForCharacterAndNonCharacter(): void
    {
        $handler = $this->createBuffHandler();

        $character = $this->createCharacter();
        $this->assertSame([], $handler->getBuffs($character));

        $buff = new Buff(id: "b1", name: "Haste", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);
        $character->setProperty(BuffHandler::BuffPropertyName, [$buff]);
        $this->assertSame([$buff], $handler->getBuffs($character));

        $fighter = $this->createDummyFighter();
        $this->assertSame([], $handler->getBuffs($fighter));

        $fighter->kwargs = [BuffHandler::BuffPropertyName => [$buff]];
        $this->assertSame([$buff], $handler->getBuffs($fighter));
    }

    public function testSetBuffsWithArrayAndBuffList(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method("debug");
        $diceBag = $this->createStub(DiceBagInterface::class);

        $handler = $this->createBuffHandler(logger: $logger, diceBag: $diceBag);

        $character = $this->createCharacter();
        $buff1 = new Buff(id: "b1", name: "Buff 1", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);
        $buff2 = new Buff(id: "b2", name: "Buff 2", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);

        $handler->setBuffs($character, [$buff1]);
        $this->assertSame([$buff1], $handler->getBuffs($character));

        $buffList = new BuffList($logger, $diceBag, [$buff1, $buff2]);
        $handler->setBuffs($character, $buffList);
        $this->assertSame([$buff1, $buff2], $handler->getBuffs($character));

        $fighter = $this->createDummyFighter();
        $handler->setBuffs($fighter, [$buff1]);
        $this->assertSame([$buff1], $fighter->kwargs[BuffHandler::BuffPropertyName]);

        $handler->setBuffs($fighter, $buffList);
        $this->assertSame([$buff1, $buff2], $fighter->kwargs[BuffHandler::BuffPropertyName]);
    }

    public function testAddBuffForCharacterAndNonCharacter(): void
    {
        $handler = $this->createBuffHandler();

        $character = $this->createCharacter();
        $buff1 = new Buff(id: "b1", name: "Buff 1", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);
        $buff2 = new Buff(id: "b2", name: "Buff 2", activatesAt: Buff::ACTIVATES_ON_ROUNDSTART, rounds: 1);

        $handler->addBuff($character, $buff1);
        $this->assertSame([$buff1], $handler->getBuffs($character));

        $handler->addBuff($character, $buff2);
        $this->assertSame([$buff1, $buff2], $handler->getBuffs($character));

        $fighter = $this->createDummyFighter();

        $handler->addBuff($fighter, $buff1);
        $this->assertSame([$buff1], $fighter->kwargs[BuffHandler::BuffPropertyName]);
    }

    public function testAddBuffFromPrototypeWithStaticValues(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method("debug");
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->never())->method("evaluateInteger");
        $expressionService->expects($this->never())->method("evaluateFloat");
        $expressionService->expects($this->never())->method("evaluateBoolean");

        $handler = $this->createBuffHandler(logger: $logger, expressionService: $expressionService);
        $character = $this->createCharacter();

        $protoBuff = new ProtoBuff(
            id: "proto_static",
            name: "Static Buff",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 3,
            startMessage: "Starts",
            roundMessage: "Round",
            endMessage: "Ends",
            effectSuccessMessage: "Success",
            effectFailsMessage: "Fails",
            noEffectMessage: "No effect",
            newDayMessage: "New day",
            expiresOnNewDay: true,
            expiresAfterBattle: false,
            badGuyRegeneration: 10,
            goodGuyRegeneration: 20,
            badGuyLifeTap: 0.1,
            goodGuyLifeTap: 0.2,
            badGuyDamageReflection: 0.05,
            goodGuyDamageReflection: 0.15,
            badGuyDamageModifier: 1.2,
            goodGuyDamageModifier: 1.5,
            badGuyAttackModifier: 1.1,
            goodGuyAttackModifier: 1.3,
            badGuyDefenseModifier: 0.9,
            goodGuyDefenseModifier: 1.4,
            badGuyInvulnerable: false,
            goodGuyInvulnerable: true,
            numberOfMinions: 2,
            minionMinBadGuyDamage: 5,
            minionMaxBadGuyDamage: 15,
            minionMinGoodGuyDamage: 1,
            minionMaxGoodGuyDamage: 8,
        );

        $handler->addBuffFromPrototype($character, $protoBuff);

        $buffs = $handler->getBuffs($character);
        $this->assertCount(1, $buffs);
        $buff = $buffs[0];
        $this->assertSame("proto_static", $buff->id);
        $this->assertSame(10, $buff->badGuyRegeneration);
        $this->assertSame(20, $buff->goodGuyRegeneration);
        $this->assertSame(0.1, $buff->badGuyLifeTap);
        $this->assertSame(0.2, $buff->goodGuyLifeTap);
        $this->assertSame(0.05, $buff->badGuyDamageReflection);
        $this->assertSame(0.15, $buff->goodGuyDamageReflection);
        $this->assertSame(1.2, $buff->badGuyDamageModifier);
        $this->assertSame(1.5, $buff->goodGuyDamageModifier);
        $this->assertSame(1.1, $buff->badGuyAttackModifier);
        $this->assertSame(1.3, $buff->goodGuyAttackModifier);
        $this->assertSame(0.9, $buff->badGuyDefenseModifier);
        $this->assertSame(1.4, $buff->goodGuyDefenseModifier);
        $this->assertFalse($buff->badGuyInvulnerable);
        $this->assertTrue($buff->goodGuyInvulnerable);
        $this->assertSame(2, $buff->numberOfMinions);
        $this->assertSame(5, $buff->minionMinBadGuyDamage);
        $this->assertSame(15, $buff->minionMaxBadGuyDamage);
        $this->assertSame(1, $buff->minionMinGoodGuyDamage);
        $this->assertSame(8, $buff->minionMaxGoodGuyDamage);
    }

    public function testAddBuffFromPrototypeWithExpressionStrings(): void
    {
        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->expects($this->exactly(7))
            ->method("evaluateInteger")
            ->willReturn(10);

        $expressionService->expects($this->exactly(10))
            ->method("evaluateFloat")
            ->willReturn(1.5);

        $expressionService->expects($this->exactly(2))
            ->method("evaluateBoolean")
            ->willReturn(true);

        $handler = $this->createBuffHandler(expressionService: $expressionService);
        $character = $this->createCharacter();

        $protoBuff = new ProtoBuff(
            id: "proto_expr",
            name: "Expression Buff",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 2,
            badGuyRegeneration: "level * 2",
            goodGuyRegeneration: "level * 5",
            badGuyLifeTap: "0.1 * level",
            goodGuyLifeTap: "0.2 * level",
            badGuyDamageReflection: "0.05 * level",
            goodGuyDamageReflection: "0.15 * level",
            badGuyDamageModifier: "1.0 + level * 0.1",
            goodGuyDamageModifier: "1.0 + level * 0.2",
            badGuyAttackModifier: "1.0 + level * 0.05",
            goodGuyAttackModifier: "1.0 + level * 0.15",
            badGuyDefenseModifier: "1.0 - level * 0.05",
            goodGuyDefenseModifier: "1.0 + level * 0.25",
            badGuyInvulnerable: "level > 100",
            goodGuyInvulnerable: "level > 10",
            numberOfMinions: "level / 2",
            minionMinBadGuyDamage: "level",
            minionMaxBadGuyDamage: "level * 3",
            minionMinGoodGuyDamage: "level + 1",
            minionMaxGoodGuyDamage: "level + 5",
        );

        $handler->addBuffFromPrototype($character, $protoBuff);

        $buffs = $handler->getBuffs($character);
        $this->assertCount(1, $buffs);
        $buff = $buffs[0];
        $this->assertSame("proto_expr", $buff->id);
        $this->assertSame(10, $buff->badGuyRegeneration);
        $this->assertSame(10, $buff->goodGuyRegeneration);
        $this->assertSame(1.5, $buff->goodGuyLifeTap);
        $this->assertTrue($buff->goodGuyInvulnerable);
    }

    public function testExpireBuffsOnNewDay(): void
    {
        $handler = $this->createBuffHandler();
        $character = $this->createCharacter();

        $expiringBuff = new Buff(
            id: "b_exp",
            name: "Expiring Buff",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 1,
            expiresOnNewDay: true,
        );

        $survivingBuffWithoutMsg = new Buff(
            id: "b_surv_no_msg",
            name: "Surviving Buff No Msg",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 1,
            expiresOnNewDay: false,
            newDayMessage: null,
        );

        $survivingBuffWithMsg = new Buff(
            id: "b_surv_msg",
            name: "Surviving Buff With Msg",
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 1,
            expiresOnNewDay: false,
            newDayMessage: "Your buff is still glowing today!",
        );

        $character->setProperty(BuffHandler::BuffPropertyName, [
            $expiringBuff,
            $survivingBuffWithoutMsg,
            $survivingBuffWithMsg,
        ]);

        $stage = new Stage();
        $stage->owner = $character;
        $action = new Action();
        $scene = new Scene();
        $event = new StageChangeEvent($stage, $action, $scene);

        $handler->expireBuffsOnNewDay($event);

        $remainingBuffs = $handler->getBuffs($character);
        $this->assertSame([$survivingBuffWithoutMsg, $survivingBuffWithMsg], $remainingBuffs);

        $paragraphs = array_values($stage->paragraphs);
        $this->assertCount(1, $paragraphs);
        $this->assertSame("lotgd2.paragraph.BuffHandler.OnNewDay.0", $paragraphs[0]->id);
        $this->assertSame("Your buff is still glowing today!", $paragraphs[0]->text);
    }
}
