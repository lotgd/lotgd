<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Repository\StageRepository;

/**
 * @phpstan-type AttachmentType array{attachment: Attachment, config: array<mixed>, data: array<string ,mixed>}
 */
#[ORM\Entity(repositoryClass: StageRepository::class)]
#[ORM\HasLifecycleCallbacks()]
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

    /** @var array<string, ActionGroup> */
    #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
    private array $actionGroups = [];

    /** @var null|array<int, AttachmentType> */
    #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
    private ?array $attachments = null;

    /** @var null|array<string, mixed> */
    #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
    private ?array $context = null;

    #[ORM\PreFlush()]
    public function preUpdate(): void
    {
        $this->actionGroups = array_map(fn ($x) => clone $x, $this->actionGroups);
    }

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

    public function addDescription(string $description, string $prefix = "\n\n"): static
    {
        $this->description .= $prefix . $description;
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

    /**
     * @return array<string, ActionGroup>
     */
    public function getActionGroups(): array
    {
        return $this->actionGroups;
    }

    public function addActionGroup(ActionGroup $actionGroup): self
    {
        $this->actionGroups[$actionGroup->getId()] = $actionGroup;
        return $this;
    }

    public function addAction(string|ActionGroup $actionGroup, Action $action): self
    {
        if ($actionGroup instanceof ActionGroup) {
            $actionGroupId = $actionGroup->getId();
        } else {
            $actionGroupId = $actionGroup;
        }

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

    /**
     * @param Attachment $attachment
     * @param array<mixed> $config
     * @param array<string, mixed> $data
     * @return $this
     */
    public function addAttachment(Attachment $attachment, array $config = [], array $data = []): static
    {
        $this->attachments[] = [
            "attachment" => $attachment,
            "config" => $config,
            "data" => $data,
        ];
        return $this;
    }

    /**
     * @return null|array<int, AttachmentType>
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * @param null|array<int, AttachmentType> $attachments
     * @return $this
     */
    public function setAttachments(?array $attachments): static
    {
        foreach ($attachments as $attachment) {
            if (!$attachment instanceof Attachment) {
                throw new \TypeError("Attachment argument must be an instance of Attachment");
            }
        }
        $this->attachments = $attachments;

        return $this;
    }

    public function clearAttachments(): self
    {
        $this->attachments = [];
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     * @return $this
     */
    public function setContext(?array $context): static
    {
        $this->context = $context;
        return $this;
    }

    public function addContext(string $key, mixed $value): self
    {
        if (!is_array($this->context)) {
            $this->context = [];
        }

        $this->context[$key] = $value;
        return $this;
    }

    public function clearContext(): self
    {
        $this->context = [];
        return $this;
    }
}
