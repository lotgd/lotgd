<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentCharacterFighter::class)]
#[UsesClass(Health::class)]
#[UsesClass(Stats::class)]
#[UsesClass(Equipment::class)]
class CurrentCharacterFighterTest extends TestCase
{
    public function testCharacterIsClonedProperlyAsAFighter(): void
    {
        $character = $this->createMock(Character::class);
        $character->method("getLevel")->willReturn(1);
        $character->method("getName")->willReturn("Character");
        $character->method("getProperty")->willReturnMap([
            [Health::HealthPropertyName, 10, 10],
            [Stats::AttackPropertyName, 1, 1],
            [Stats::DefensePropertyName, 1, 2],
            [Health::MaxHealthPropertyName, 10, 10],
        ]);

        $fighter = CurrentCharacterFighter::fromCharacter($character);

        $this->assertSame("Character", $fighter->name);
        $this->assertSame(10, $fighter->health);
        $this->assertSame(1, $fighter->attack);
        $this->assertSame(2, $fighter->defense);
    }
}
