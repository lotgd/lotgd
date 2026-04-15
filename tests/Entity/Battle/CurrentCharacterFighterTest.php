<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentCharacterFighter::class)]
#[UsesClass(HealthHandler::class)]
#[UsesClass(StatsHandler::class)]
#[UsesClass(EquipmentHandler::class)]
#[AllowMockObjectsWithoutExpectations]
class CurrentCharacterFighterTest extends TestCase
{
    public function testCharacterIsClonedProperlyAsAFighter(): void
    {
        $character = $this->createMock(Character::class);
        $character->method(PropertyHook::get("level"))->willReturn(1);
        $character->method(PropertyHook::get("name"))->willReturn("Character");
        $character->method("getProperty")->willReturnMap([
            [HealthHandler::HealthPropertyName, 10, 10],
            [StatsHandler::AttackPropertyName, 1, 1],
            [StatsHandler::DefensePropertyName, 1, 2],
            [HealthHandler::MaxHealthPropertyName, 10, 10],
        ]);

        $fighter = CurrentCharacterFighter::fromCharacter($character);

        $this->assertSame("Character", $fighter->name);
        $this->assertSame(10, $fighter->health);
        $this->assertSame(1, $fighter->attack);
        $this->assertSame(2, $fighter->defense);
    }
}
