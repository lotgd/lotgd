<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\BattleRoundMessage;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Health;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Battle\BattleMessage;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(BattleState::class)]
#[UsesClass(BattleMessage::class)]
#[UsesClass(BattleRoundMessage::class)]
#[UsesClass(Health::class)]
class BattleStateTest extends TestCase
{
    private FighterInterface&MockObject $goodGuyMock;
    private FighterInterface&MockObject $badGuyMock;
    private BattleState $battleState;

    protected function setUp(): void
    {
        $this->goodGuyMock = $this->createMock(FighterInterface::class);
        $this->badGuyMock = $this->createMock(FighterInterface::class);
        
        $this->battleState = new BattleState(
            goodGuy: $this->goodGuyMock,
            badGuy: $this->badGuyMock
        );
    }

    public function testConstructorWithDefaultValues(): void
    {
        $battleState = new BattleState(
            goodGuy: $this->goodGuyMock,
            badGuy: $this->badGuyMock
        );

        $this->assertSame($this->goodGuyMock, $battleState->goodGuy);
        $this->assertSame($this->badGuyMock, $battleState->badGuy);
        $this->assertTrue($battleState->isLevelAdjustmentEnabled);
        $this->assertTrue($battleState->isCriticalHitEnabled);
        $this->assertTrue($battleState->isRiposteEnabled);
        $this->assertEquals(0, $battleState->roundCounter);
        $this->assertEquals([], $battleState->messageRounds);
        $this->assertEquals(BattleStateStatusEnum::Undecided, $battleState->result);
    }

    public function testConstructorWithCustomValues(): void
    {
        $battleState = new BattleState(
            goodGuy: $this->goodGuyMock,
            badGuy: $this->badGuyMock,
            isLevelAdjustmentEnabled: false,
            isCriticalHitEnabled: false,
            isRiposteEnabled: false
        );

        $this->assertFalse($battleState->isLevelAdjustmentEnabled);
        $this->assertFalse($battleState->isCriticalHitEnabled);
        $this->assertFalse($battleState->isRiposteEnabled);
    }

    public function testSetCharacter(): void
    {
        $characterMock = $this->createMock(Character::class);

        $this->battleState->setCharacter($characterMock);

        $this->assertSame($characterMock, $this->battleState->character);
    }

    public function testSetCharacterMultipleTimes(): void
    {
        $character1 = $this->createMock(Character::class);
        $character2 = $this->createMock(Character::class);

        $this->battleState->setCharacter($character1);
        $this->assertSame($character1, $this->battleState->character);

        $this->battleState->setCharacter($character2);
        $this->assertSame($character2, $this->battleState->character);
    }

    public function testSynchronizeToCharacterWithoutCharacterThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("You must set the character first before synchronizing");

        $this->battleState->syncronizeToCharacter();
    }

    public function testSynchronizeToCharacterWithoutCharacterSynchronizes(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())->method("setProperty")->with(Health::HealthPropertyName, 10);

        $goodGuy = $this->createMock(CurrentCharacterFighter::class);
        $goodGuy->expects($this->once())->method(PropertyHook::get("health"))->willReturn(10);

        $battleState = new BattleState(
            goodGuy: $goodGuy,
            badGuy: $this->badGuyMock
        );

        $battleState->setCharacter($character);

        $battleState->syncronizeToCharacter();
    }

    public function testIncrementRound(): void
    {
        $this->assertEquals(0, $this->battleState->roundCounter);

        $this->battleState->incrementRound();
        $this->assertEquals(1, $this->battleState->roundCounter);

        $this->battleState->incrementRound();
        $this->assertEquals(2, $this->battleState->roundCounter);
    }

    public function testAddMessagesCreatesNewRound(): void
    {
        $message1 = new BattleMessage('Attack successful', ['damage' => 10]);
        $message2 = new BattleMessage('Dodge avoided', ['target' => 'badGuy']);

        $this->battleState->addMessages([$message1, $message2]);

        $this->assertCount(1, $this->battleState->messageRounds);
        $this->assertEquals(0, $this->battleState->messageRounds[0]->round);
        $this->assertCount(2, $this->battleState->messageRounds[0]->messages);
    }

    public function testAddMessagesMultipleRounds(): void
    {
        $message1 = new BattleMessage('Round 0 attack', []);
        $this->battleState->addMessages([$message1]);

        $this->battleState->incrementRound();

        $message2 = new BattleMessage('Round 1 defense', []);
        $this->battleState->addMessages([$message2]);

        $this->assertCount(2, $this->battleState->messageRounds);
        $this->assertEquals(0, $this->battleState->messageRounds[0]->round);
        $this->assertEquals(1, $this->battleState->messageRounds[1]->round);
    }

    public function testAddMessagesWithEmptyIterable(): void
    {
        $this->battleState->addMessages([]);

        $this->assertCount(1, $this->battleState->messageRounds);
        $this->assertCount(0, $this->battleState->messageRounds[0]->messages);
    }

    public function testIsOverReturnsFalseWhenUndecided(): void
    {
        $this->battleState->result = BattleStateStatusEnum::Undecided;

        $this->assertFalse($this->battleState->isOver());
    }

    public function testIsOverReturnsTrueWhenGoodGuyWon(): void
    {
        $this->battleState->result = BattleStateStatusEnum::GoodGuyWon;

        $this->assertTrue($this->battleState->isOver());
    }

    public function testIsOverReturnsTrueWhenBadGuyWon(): void
    {
        $this->battleState->result = BattleStateStatusEnum::BadGuyWon;

        $this->assertTrue($this->battleState->isOver());
    }

    public function testBattleStateCompleteFlow(): void
    {
        // Initial state
        $this->assertEquals(0, $this->battleState->roundCounter);
        $this->assertFalse($this->battleState->isOver());

        // Round 1
        $this->battleState->addMessages([new BattleMessage('goodGuy attacks', [])]);
        $this->battleState->incrementRound();

        // Round 2
        $this->battleState->addMessages([new BattleMessage('badGuy counters', [])]);
        $this->battleState->incrementRound();

        // Battle ends
        $this->battleState->result = BattleStateStatusEnum::GoodGuyWon;

        $this->assertEquals(2, $this->battleState->roundCounter);
        $this->assertTrue($this->battleState->isOver());
        $this->assertCount(2, $this->battleState->messageRounds);
    }

    public function testCharacterPropertyInitiallyNull(): void
    {
        $this->assertNull($this->battleState->character);
    }

    public function testMessageRoundsAreStoredWithCorrectRoundNumbers(): void
    {
        $this->battleState->addMessages([new BattleMessage('msg1', [])]);
        $this->battleState->incrementRound();
        $this->battleState->addMessages([new BattleMessage('msg2', [])]);
        $this->battleState->incrementRound();
        $this->battleState->addMessages([new BattleMessage('msg3', [])]);

        $this->assertEquals(0, $this->battleState->messageRounds[0]->round);
        $this->assertEquals(1, $this->battleState->messageRounds[1]->round);
        $this->assertEquals(2, $this->battleState->messageRounds[2]->round);
    }
}