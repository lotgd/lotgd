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
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $suffix = null;

    #[ORM\Column(type: Types::SMALLINT, options: ["default" => 0])]
    private ?int $level = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?Stage $stage = null;

    /** @var null|array<string, mixed>  */
    #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
    private ?array $properties;

    public function __construct()
    {
        $this->properties = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function setSuffix(?string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setStage(?Stage $stage): static
    {
        if (!$stage) {
            $this->stage = null;
            return $this;
        }

        // set the owning side of the relation if necessary
        if ($stage->getOwner() !== $this) {
            $stage->setOwner($this);
        }

        $this->stage = $stage;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    /**
     * @param array<string, mixed> $properties
     * @return $this
     */
    public function setProperties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    public function setProperty(string $name, mixed $value): static
    {
        $this->properties[$name] = $value;
        return $this;
    }
}
