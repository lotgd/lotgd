<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Race;
use LotGD2\Game\Race\RaceInterface;
use LotGD2\Game\Race\StandardRace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ValueError;

/**
 * @phpstan-import-type StandardRaceConfiguration from StandardRace
 */
#[CoversClass(Race::class)]
#[UsesClass(StandardRace::class)]
class RaceTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $race = new Race();

        $this->assertNull($race->id);
        $this->assertNull($race->name);
        $this->assertNull($race->description);
        $this->assertNull($race->selectionText);
        $this->assertNull($race->className);
        $this->assertSame([], $race->configuration);
    }

    public function testConstructorWithArguments(): void
    {
        $race = new Race(
            name: "Elf",
            description: "Agile forest dwellers",
            selectionText: "Choose Elf for agility",
            className: StandardRace::class,
            configuration: ["attack" => 2],
        );

        $this->assertNull($race->id);
        $this->assertSame("Elf", $race->name);
        $this->assertSame("Agile forest dwellers", $race->description);
        $this->assertSame("Choose Elf for agility", $race->selectionText);
        $this->assertSame(StandardRace::class, $race->className);
        $this->assertSame(["attack" => 2], $race->configuration);
    }

    public function testIdProperty(): void
    {
        $race = new Race();
        $this->assertNull($race->id);

        $reflection = new ReflectionProperty(Race::class, "id");
        $reflection->setValue($race, 10);

        $this->assertSame(10, $race->id);
    }

    public function testPropertyModification(): void
    {
        /** @var Race<array<string, mixed>> $race */
        $race = new Race();

        $race->name = "Dwarf";
        $race->description = "Sturdy miners";
        $race->selectionText = "Choose Dwarf for endurance";
        $race->configuration = ["bonusDefense" => 3];

        $this->assertSame("Dwarf", $race->name);
        $this->assertSame("Sturdy miners", $race->description);
        $this->assertSame("Choose Dwarf for endurance", $race->selectionText);
        $this->assertSame(["bonusDefense" => 3], $race->configuration);

        $race->name = null;
        $race->description = null;
        $race->selectionText = null;
        $race->configuration = [];

        $this->assertNull($race->name);
        $this->assertNull($race->description);
        $this->assertNull($race->selectionText);
        $this->assertSame([], $race->configuration);
    }

    public function testClassNameWithValidSubclass(): void
    {
        /** @var Race<StandardRaceConfiguration> $race */
        $race = new Race();

        $race->className = StandardRace::class;
        $this->assertSame(StandardRace::class, $race->className);

        $race->className = null;
        $this->assertNull($race->className);
    }

    public function testClassNameWithInvalidClassThrowsValueError(): void
    {
        $race = new Race();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Race class must implement " . RaceInterface::class);

        $race->className = \stdClass::class;
    }

    public function testConstructorWithInvalidClassThrowsValueError(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Race class must implement " . RaceInterface::class);

        new Race(
            name: "Invalid",
            className: \stdClass::class,
        );
    }

    #[TestWith([1, "Elf", "<Race#1 Elf>"])]
    #[TestWith([null, "Dwarf", "<Race# Dwarf>"])]
    #[TestWith([42, null, "<Race#42 >"])]
    #[TestWith([null, null, "<Race# >"])]
    public function testToString(?int $id, ?string $name, string $expectedString): void
    {
        $race = new Race(name: $name);

        if ($id !== null) {
            $reflection = new ReflectionProperty(Race::class, "id");
            $reflection->setValue($race, $id);
        }

        $this->assertSame($expectedString, (string) $race);
        $this->assertSame($expectedString, $race->__toString());
    }
}
