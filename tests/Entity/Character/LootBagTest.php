<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Character;

use LogicException;
use LotGD2\Entity\Character\LootBag;
use LotGD2\Entity\Character\LootPosition;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(LootBag::class)]
#[UsesClass(DiceBag::class)]
class LootBagTest extends TestCase
{
    #[TestWith([null, false, false, true])]
    #[TestWith([false, false, false, true])]
    #[TestWith([true, false, true, true])]
    #[TestWith([false, true, false, false])]
    #[TestWith([true, true, true, false])]
    public function testConstructor(
        ?bool $locked,
        bool $diceBag,
        bool $expectedLocked,
        bool $expectStubDiceBag
    ) {
        $diceBag = $diceBag ? $this->createStub(DiceBagInterface::class) : null;

        if ($locked === null and $diceBag === null) {
            $lootBag = new LootBag();
        } elseif ($diceBag === null) {
            $lootBag = new LootBag($locked);
        } else {
            $lootBag = new LootBag($locked, $diceBag);
        }

        $this->assertSame($expectedLocked,  $lootBag->locked);

        if ($expectStubDiceBag === true) {
            $this->assertInstanceOf(DiceBag::class, $lootBag->diceBag);
        } else {
            $this->assertSame($diceBag, $lootBag->diceBag);
        }
    }

    public function testAddPosition(): void
    {
        $diceBag = $this->createStub(DiceBagInterface::class);
        $lootBag = new LootBag(false, $diceBag);

        $positions = [
            "id-1" => $this->createMock(LootPosition::class),
            "id-2" => $this->createMock(LootPosition::class),
            "id-3" => $this->createMock(LootPosition::class),
        ];

        foreach ($positions as $id => $position) {
            $position
                ->expects($this->once())
                ->method(PropertyHook::get("id"))
                ->willReturn($id);

            $lootBag->add($position);
        }

        $this->assertCount(3, $lootBag->positions);
    }

    public function testGetPosition(): void
    {
        $diceBag = $this->createStub(DiceBagInterface::class);
        $lootBag = new LootBag(false, $diceBag);

        $positions = [
            "id-1" => $this->createStub(LootPosition::class),
            "id-2" => $this->createStub(LootPosition::class),
            "id-3" => $this->createStub(LootPosition::class),
        ];

        foreach ($positions as $id => $position) {
            $position
                ->method(PropertyHook::get("id"))
                ->willReturn($id);

            $lootBag->add($position);
        }

        foreach ($positions as $id => $position) {
            $this->assertSame($position, $lootBag->get($id));
        }

        // Test that null is returned of position is not known
        $this->assertNull($lootBag->get("id-4"));
    }

    public function testIfLockSetsLockedToTrue()
    {
        $diceBag = $this->createStub(DiceBagInterface::class);
        $lootBag = new LootBag(false, $diceBag);

        $lootBag->lock();

        $this->assertTrue($lootBag->locked);
    }

    public function testIfAddingLootPositionToLockedLootBagThrowsException()
    {
        $diceBag = $this->createStub(DiceBagInterface::class);
        $lootBag = new LootBag(true, $diceBag);

        $this->expectException(LogicException::class);
        $lootBag->add($this->createStub(LootPosition::class));
    }

    public function testIfLockAddsLockedLootPositions(): void
    {
        $diceBag = $this->createStub(DiceBagInterface::class);
        $lootBag = new LootBag(false, $diceBag);

        $positions = [
            "id-1" => $this->createMock(LootPosition::class),
            "id-2" => $this->createMock(LootPosition::class),
            "id-3" => $this->createMock(LootPosition::class),
        ];

        $newPositions = [];

        foreach ($positions as $id => $position) {
            $otherPosition = $this->createStub(LootPosition::class);
            $otherPosition
                ->method(PropertyHook::get("id"))
                ->willReturn($id);
            $otherPosition
                ->method(PropertyHook::get("locked"))
                ->willReturn(true);

            $position
                ->method(PropertyHook::get("id"))
                ->willReturn($id);
            $position
                ->expects($this->once())
                ->method("getLockedCopy")
                ->willReturn($otherPosition);

            $newPositions[$id] = $otherPosition;

            $lootBag->add($position);
        }

        $this->assertSame($positions, $lootBag->positions);

        $lootBag->lock();

        $this->assertCount(3, $lootBag->positions);
        $this->assertSame($newPositions, $lootBag->positions);
    }
}
