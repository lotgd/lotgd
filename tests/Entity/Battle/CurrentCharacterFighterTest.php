<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
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
#[UsesClass(HandlerRegistry::class)]
class CurrentCharacterFighterTest extends TestCase
{
    public function testCharacterIsClonedProperlyAsAFighter(): void
    {
        $character = $this->createStub(Character::class);
        $character->method(PropertyHook::get("level"))->willReturn(1);
        $character->method(PropertyHook::get("name"))->willReturn("Character");

        $healthHandler = $this->createMock(HealthHandler::class);
        $healthHandler
            ->expects($this->once())
            ->method("getHealth")
            ->willReturn(10);
        $healthHandler
            ->expects($this->once())
            ->method("getMaxHealth")
            ->willReturn(10);

        $statsHandler = $this->createMock(StatsHandler::class);
        $statsHandler
            ->expects($this->once())
            ->method("getTotalAttack")
            ->willReturn(1);
        $statsHandler
            ->expects($this->once())
            ->method("getTotalDefense")
            ->willReturn(2);
        $statsHandler
            ->method("getLevel")
            ->willReturn(1);

        $equipmentHandler = $this->createMock(EquipmentHandler::class);
        $equipmentHandler
            ->expects($this->once())
            ->method("getItemInSlot")
            ->with($character, EquipmentHandler::WeaponSlot)
            ->willReturn(new EquipmentItem(
                name: "A flask with picrinic acid",
                strength: 1,
                value: 100,
            ));

        $gameStateService = $this->createStub(GameStateService::class);

        $handler = $this->createStub(HandlerRegistry::class);
        $handler->method("get")->willReturnMap([
            [HealthHandler::class, $healthHandler],
            [StatsHandler::class, $statsHandler],
            [EquipmentHandler::class, $equipmentHandler],
        ]);

        $fighter = CurrentCharacterFighter::fromCharacter($gameStateService, $handler, $character);

        $this->assertSame("Character", $fighter->name);
        $this->assertSame(10, $fighter->health);
        $this->assertSame(1, $fighter->attack);
        $this->assertSame(2, $fighter->defense);
    }
}
