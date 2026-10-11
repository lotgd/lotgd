<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Character\SpecialtySkill;
use LotGD2\Entity\Mapped\Specialty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Specialty::class)]
#[UsesClass(SpecialtySkill::class)]
#[UsesClass(ProtoBuff::class)]
class SpecialtyTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $specialty = new Specialty();

        $this->assertNull($specialty->id);
        $this->assertNull($specialty->name);
        $this->assertNull($specialty->description);
        $this->assertNull($specialty->selectionText);
        $this->assertNull($specialty->className);
        $this->assertSame([], $specialty->skills);
        $this->assertSame([], $specialty->configuration);
    }

    public function testConstructorWithArguments(): void
    {
        $buff = new ProtoBuff(id: "shadow_cloak", name: "Shadow Cloak", activatesAt: ProtoBuff::ACTIVATES_ANY, rounds: 3);
        $skill = new SpecialtySkill("Shadow Strike", 5, $buff);
        $specialty = new Specialty(
            name: "Dark Arts",
            description: "Harness shadow magic",
            selectionText: "Choose Dark Arts for offensive spells",
            className: \stdClass::class,
            skills: [$skill],
            configuration: ["manaCostReduction" => 10],
        );

        $this->assertNull($specialty->id);
        $this->assertSame("Dark Arts", $specialty->name);
        $this->assertSame("Harness shadow magic", $specialty->description);
        $this->assertSame("Choose Dark Arts for offensive spells", $specialty->selectionText);
        $this->assertSame(\stdClass::class, $specialty->className);
        $this->assertSame([$skill], $specialty->skills);
        $this->assertSame(["manaCostReduction" => 10], $specialty->configuration);
    }

    public function testIdProperty(): void
    {
        $specialty = new Specialty();
        $this->assertNull($specialty->id);

        $reflection = new ReflectionProperty(Specialty::class, "id");
        $reflection->setValue($specialty, 7);

        $this->assertSame(7, $specialty->id);
    }

    public function testPropertyModification(): void
    {
        /** @var Specialty<array<string, mixed>> $specialty */
        $specialty = new Specialty();
        $buff = new ProtoBuff(id: "magic_shield", name: "Magic Shield", activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART, rounds: 2);
        $skill1 = new SpecialtySkill("Heal", 3, $buff);
        $skill2 = new SpecialtySkill("Bless", 4, $buff);

        $specialty->name = "Mystic Arts";
        $specialty->description = "Ancient wisdom";
        $specialty->selectionText = "Choose Mystic Arts";
        // @phpstan-ignore assign.propertyType
        $specialty->className = "Some\\Custom\\Specialty";
        $specialty->skills = [$skill1, $skill2];
        $specialty->configuration = ["buffDuration" => 3];

        $this->assertSame("Mystic Arts", $specialty->name);
        $this->assertSame("Ancient wisdom", $specialty->description);
        $this->assertSame("Choose Mystic Arts", $specialty->selectionText);
        $this->assertSame("Some\\Custom\\Specialty", $specialty->className);
        $this->assertSame([$skill1, $skill2], $specialty->skills);
        $this->assertSame(["buffDuration" => 3], $specialty->configuration);

        $specialty->name = null;
        $specialty->description = null;
        $specialty->selectionText = null;
        $specialty->className = null;
        $specialty->skills = [];
        $specialty->configuration = [];

        $this->assertNull($specialty->name);
        $this->assertNull($specialty->description);
        $this->assertNull($specialty->selectionText);
        $this->assertNull($specialty->className);
        $this->assertSame([], $specialty->skills);
        $this->assertSame([], $specialty->configuration);
    }

    #[TestWith([1, "Dark Arts", "<Speciality#1 Dark Arts>"])]
    #[TestWith([null, "Mystic Arts", "<Speciality# Mystic Arts>"])]
    #[TestWith([42, null, "<Speciality#42 >"])]
    #[TestWith([null, null, "<Speciality# >"])]
    public function testToString(?int $id, ?string $name, string $expectedString): void
    {
        $specialty = new Specialty(name: $name);

        if ($id !== null) {
            $reflection = new ReflectionProperty(Specialty::class, "id");
            $reflection->setValue($specialty, $id);
        }

        $this->assertSame($expectedString, (string) $specialty);
        $this->assertSame($expectedString, $specialty->__toString());
    }
}
