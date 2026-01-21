<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Repository\CharacterRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get => $this->id;
    }

    /**
     * @param null|array<string, mixed> $properties
     */
    public function __construct(
        #[ORM\Column(length: 50, unique: true)]
        #[Assert\NotBlank]
        #[Assert\Length(min: 2)]
        public ?string $name = null {
            get => $this->name;
            set => $value;
        },

        #[ORM\Column(length: 50, nullable: true)]
        public ?string $title = null {
            get => $this->title;
            set => $value;
        },

        #[ORM\Column(length: 50, nullable: true)]
        public ?string $suffix = null {
            get => $this->suffix;
            set => $value;
        },

        #[ORM\Column(type: Types::SMALLINT, options: ["default" => 0])]
        public ?int $level = null {
            get => $this->level;
            set => $value;
        },

        #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
        public ?Stage $stage = null {
            get => $this->stage;
            set {
                if (is_null($value)) {
                    $this->stage = null;
                    return;
                }

                // set the owning side of the relation if necessary
                if ($value->getOwner() !== $this) {
                    $value->setOwner($this);
                }

                $this->stage = $value;
            }
        },

        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $properties = [] {
            get => $this->properties;
            set => $value;
        },
    ) {
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    public function setProperty(string $name, mixed $value): static
    {
        $properties = $this->properties;
        $properties[$name] = $value;
        $this->properties = $properties;
        return $this;
    }
}
