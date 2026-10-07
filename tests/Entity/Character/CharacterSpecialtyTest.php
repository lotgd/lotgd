<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Character;

use LotGD2\Entity\Character\CharacterSpecialty;
use LotGD2\Entity\Mapped\Specialty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(CharacterSpecialty::class)]
#[UsesClass(Specialty::class)]
class CharacterSpecialtyTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $characterSpecialty = new CharacterSpecialty();

        $this->assertSame(0, $characterSpecialty->level);
        $this->assertSame(0, $characterSpecialty->uses);
        $this->assertFalse(isset($characterSpecialty->id));
        $this->assertFalse(isset($characterSpecialty->name));
        $this->assertFalse(isset($characterSpecialty->className));
        $this->assertFalse(isset($characterSpecialty->configuration));
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[TestWith([1, "Thievery", \stdClass::class, ["bonus" => 10]])]
    #[TestWith([null, "Mystic Arts", null, []])]
    #[TestWith([42, "Dark Magic", "Some\\Class\\Name", ["rank" => 3, "enabled" => true]])]
    public function testConstructorWithSpecialty(
        ?int $id,
        ?string $name,
        ?string $className,
        array $configuration,
    ): void {
        $specialty = new Specialty(
            name: $name,
            description: "Description of specialty",
            selectionText: "Choose this specialty",
            className: $className,
            skills: [],
            configuration: $configuration,
        );

        if ($id !== null) {
            $reflection = new ReflectionProperty(Specialty::class, "id");
            $reflection->setValue($specialty, $id);
        }

        $characterSpecialty = new CharacterSpecialty($specialty);

        $this->assertSame($id, $characterSpecialty->id);
        $this->assertSame($name, $characterSpecialty->name);
        $this->assertSame($className, $characterSpecialty->className);
        $this->assertSame($configuration, $characterSpecialty->configuration);
        $this->assertSame(0, $characterSpecialty->level);
        $this->assertSame(0, $characterSpecialty->uses);
    }

    public function testPropertyModification(): void
    {
        $characterSpecialty = new CharacterSpecialty();

        $characterSpecialty->id = 99;
        $characterSpecialty->name = "Elementalism";
        $characterSpecialty->className = \stdClass::class;
        $characterSpecialty->configuration = ["key" => "value"];
        $characterSpecialty->level = 5;
        $characterSpecialty->uses = 12;

        $this->assertSame(99, $characterSpecialty->id);
        $this->assertSame("Elementalism", $characterSpecialty->name);
        $this->assertSame(\stdClass::class, $characterSpecialty->className);
        $this->assertSame(["key" => "value"], $characterSpecialty->configuration);
        $this->assertSame(5, $characterSpecialty->level);
        $this->assertSame(12, $characterSpecialty->uses);
    }

    #[TestWith([1, "Dark Arts", "<CharacterSpecialty#1 Dark Arts>"])]
    #[TestWith([null, "Mystic", "<CharacterSpecialty# Mystic>"])]
    #[TestWith([42, null, "<CharacterSpecialty#42 >"])]
    #[TestWith([null, null, "<CharacterSpecialty# >"])]
    public function testToString(?int $id, ?string $name, string $expectedString): void
    {
        $characterSpecialty = new CharacterSpecialty();
        $characterSpecialty->id = $id;
        $characterSpecialty->name = $name;

        $this->assertSame($expectedString, (string) $characterSpecialty);
        $this->assertSame($expectedString, $characterSpecialty->__toString());
    }
}
