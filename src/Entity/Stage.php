<?php

namespace LotGD2\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Repository\StageRepository;

#[ApiResource]
#[ORM\Entity(repositoryClass: StageRepository::class)]
class Stage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'stage', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Character $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Scene $scene = null;

    /** @var ActionGroup[] */
    #[ORM\Column(type: "json_document", nullable: true)]
    private array $actionGroups = [];

    #[ORM\Column(type: "json_document", nullable: true)]
    private ?array $attachments = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Character
    {
        return $this->owner;
    }

    public function setOwner(Character $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    public function setScene(?Scene $scene): static
    {
        $this->scene = $scene;

        return $this;
    }

    public function getActionGroups(): array
    {
        return $this->actionGroups;
    }

    public function addActionGroup(ActionGroup $actionGroup): self
    {
        $this->actionGroups[$actionGroup->getId()] = $actionGroup;
        return $this;
    }

    public function addAction($actionGroupId, Action $action): self
    {
        if (array_key_exists($actionGroupId, $this->actionGroups)) {
            $this->actionGroups[$actionGroupId]->addAction($action);
        }
        return $this;
    }

    public function clearActionGroups(): self
    {
        $this->actionGroups = [];
        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): static
    {
        $this->attachments = $attachments;

        return $this;
    }
}
