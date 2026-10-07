<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Character;

use LogicException;
use LotGD2\Entity\Character\LootPosition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(LootPosition::class)]
class LootPositionTest extends TestCase
{
    /**
     * @param array<string, mixed> $loot
     */
    #[TestWith(["gold", ["amount" => 100]])]
    #[TestWith(["gems", []])]
    #[TestWith(["custom-id", ["nested" => ["key" => "value"], "count" => 42]])]
    public function testConstructorInitializesProperties(string $id, array $loot): void
    {
        $position = new LootPosition($id, $loot);

        $this->assertSame($id, $position->id);
        $this->assertSame($loot, $position->loot);
        $this->assertFalse($position->locked);
    }

    public function testSetLootWhenUnlocked(): void
    {
        $position = new LootPosition("items", ["item_1" => 1]);

        $this->assertSame(["item_1" => 1], $position->loot);

        $newContents = ["item_1" => 1, "item_2" => 5];
        $position->loot = $newContents;

        $this->assertSame($newContents, $position->loot);
    }

    public function testGetLockedCopy(): void
    {
        $originalLoot = ["gold" => 50, "xp" => 100];
        $position = new LootPosition("reward", $originalLoot);

        $lockedCopy = $position->getLockedCopy();

        $this->assertNotSame($position, $lockedCopy);
        $this->assertSame($position->id, $lockedCopy->id);
        $this->assertSame($position->loot, $lockedCopy->loot);
        $this->assertTrue($lockedCopy->locked);
        $this->assertFalse($position->locked);

        // Modifying original should not alter copy
        $position->loot = ["gold" => 200];
        $this->assertSame(["gold" => 200], $position->loot);
        $this->assertSame($originalLoot, $lockedCopy->loot);
    }

    public function testSetLootWhenLockedThrowsException(): void
    {
        $position = new LootPosition("locked-pos", ["gold" => 50]);
        $lockedCopy = $position->getLockedCopy();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot change the contents of a locked loot position.");

        $lockedCopy->loot = ["gold" => 100];
    }
}
