<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use LotGD2\Entity\Mapped\Specialty;
use Stringable;

class CharacterSpecialty implements Stringable
{
    public ?int $id;
    public ?string $name;
    /** @var null|class-string  */
    public ?string $className;
    /** @var array<string, mixed>  */
    public array $configuration;

    public int $level = 0;
    public int $uses = 0;

    /**
     * Represents a selected specialty on a character.
     *
     * Constructor parameter is optional to permit deserialization from an array.
     *
     * @param Specialty|null $specialty Use parameter to create a pre-filled version created from a specialty.
     */
    public function __construct(
        ?Specialty $specialty = null,
    ) {
        if ($specialty !== null) {
            $this->id = $specialty->id;
            $this->name = $specialty->name;
            $this->className = $specialty->className;
            $this->configuration = $specialty->configuration;
        }
    }

    public function __toString(): string
    {
        return "<CharacterSpecialty#{$this->id} {$this->name}>";
    }
}