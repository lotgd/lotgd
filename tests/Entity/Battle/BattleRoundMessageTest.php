<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\BattleRoundMessage;
use LotGD2\Entity\Battle\BattleMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BattleRoundMessage::class)]
class BattleRoundMessageTest extends TestCase
{
    private BattleRoundMessage $battleRoundMessage;

    protected function setUp(): void
    {
        $this->battleRoundMessage = new BattleRoundMessage(round: 1);
    }

    public function testConstructorSetRound(): void
    {
        $round = 5;
        $battleRoundMessage = new BattleRoundMessage(round: $round);

        $this->assertSame($round, $battleRoundMessage->round);
    }

    public function testMessagesInitializedAsEmptyArray(): void
    {
        $this->assertIsArray($this->battleRoundMessage->messages);
        $this->assertEmpty($this->battleRoundMessage->messages);
    }

    public function testAddSingleMessage(): void
    {
        $battleMessage = $this->createMock(BattleMessage::class);

        $this->battleRoundMessage->add($battleMessage);

        $this->assertCount(1, $this->battleRoundMessage->messages);
        $this->assertSame($battleMessage, $this->battleRoundMessage->messages[0]);
    }

    public function testAddMultipleMessages(): void
    {
        $battleMessage1 = $this->createMock(BattleMessage::class);
        $battleMessage2 = $this->createMock(BattleMessage::class);
        $battleMessage3 = $this->createMock(BattleMessage::class);

        $this->battleRoundMessage->add($battleMessage1);
        $this->battleRoundMessage->add($battleMessage2);
        $this->battleRoundMessage->add($battleMessage3);

        $this->assertCount(3, $this->battleRoundMessage->messages);
        $this->assertSame($battleMessage1, $this->battleRoundMessage->messages[0]);
        $this->assertSame($battleMessage2, $this->battleRoundMessage->messages[1]);
        $this->assertSame($battleMessage3, $this->battleRoundMessage->messages[2]);
    }

    public function testMessagesCanBeModifiedDirectly(): void
    {
        $battleMessage1 = $this->createMock(BattleMessage::class);
        $battleMessage2 = $this->createMock(BattleMessage::class);

        $this->battleRoundMessage->messages = [$battleMessage1, $battleMessage2];

        $this->assertCount(2, $this->battleRoundMessage->messages);
        $this->assertSame($battleMessage1, $this->battleRoundMessage->messages[0]);
        $this->assertSame($battleMessage2, $this->battleRoundMessage->messages[1]);
    }

    public function testRoundCanBeModified(): void
    {
        $this->battleRoundMessage->round = 10;

        $this->assertSame(10, $this->battleRoundMessage->round);
    }

    public function testAddPreservesExistingMessages(): void
    {
        $battleMessage1 = $this->createMock(BattleMessage::class);
        $battleMessage2 = $this->createMock(BattleMessage::class);

        $this->battleRoundMessage->add($battleMessage1);
        $firstCount = count($this->battleRoundMessage->messages);

        $this->battleRoundMessage->add($battleMessage2);

        $this->assertCount($firstCount + 1, $this->battleRoundMessage->messages);
    }

    public function testMultipleRounds(): void
    {
        $round1 = new BattleRoundMessage(round: 1);
        $round2 = new BattleRoundMessage(round: 2);

        $this->assertSame(1, $round1->round);
        $this->assertSame(2, $round2->round);
        $this->assertNotEquals($round1->round, $round2->round);
    }
}