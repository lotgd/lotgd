<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Title;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Title::class)]
class TitleTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $title = new Title();

        $this->assertNull($title->id);
        $this->assertSame(0, $title->dk);
        $this->assertNull($title->male);
        $this->assertNull($title->female);
        $this->assertNull($title->other);
    }

    public function testConstructorWithArguments(): void
    {
        $title = new Title(
            dk: 5,
            male: "Sir",
            female: "Lady",
            other: "Noble",
        );

        $this->assertNull($title->id);
        $this->assertSame(5, $title->dk);
        $this->assertSame("Sir", $title->male);
        $this->assertSame("Lady", $title->female);
        $this->assertSame("Noble", $title->other);
    }

    public function testIdProperty(): void
    {
        $title = new Title();
        $this->assertNull($title->id);

        $reflection = new ReflectionProperty(Title::class, "id");
        $reflection->setValue($title, 12);

        $this->assertSame(12, $title->id);
    }

    public function testPropertyModification(): void
    {
        $title = new Title();

        $title->dk = 10;
        $title->male = "Lord";
        $title->female = "Lady";
        $title->other = "Monarch";

        $this->assertSame(10, $title->dk);
        $this->assertSame("Lord", $title->male);
        $this->assertSame("Lady", $title->female);
        $this->assertSame("Monarch", $title->other);

        $title->male = null;
        $title->female = null;
        $title->other = null;

        $this->assertNull($title->male);
        $this->assertNull($title->female);
        $this->assertNull($title->other);
    }
}
