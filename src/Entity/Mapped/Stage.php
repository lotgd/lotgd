<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Deprecated;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Paragraph;
use LotGD2\Repository\StageRepository;
use TypeError;

/**
 * @phpstan-type AttachmentType array{
 *     attachment: Attachment,
 *     config: array<mixed>,
 *     data: array<string ,mixed>,
 * }
 */
#[ORM\Entity(repositoryClass: StageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Stage
{
    const string SceneText = "lotgd.stage.sceneText";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    /**
     * @param array<string, ActionGroup> $actionGroups
     * @param null|array<int, AttachmentType> $attachments
     * @param null|array<string, mixed> $context
     */
    public function __construct(
        #[ORM\OneToOne(inversedBy: 'stage', cascade: ['persist'])]
        #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
        public ?Character $owner = null {
            get => $this->owner;
            set => $value;
        },

        #[ORM\Column(length: 255)]
        public ?string $title = null {
            get => $this->title;
            set => $value;
        },

        /**
         * @var array<string|int, Paragraph>
         */
        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $paragraphs = null {
            get => $this->paragraphs ?? [];
            set {
                if ($value === null) {
                    $this->paragraphs = [];
                } else {
                    $paragraphs = [];

                    foreach ($value as $paragraph) {
                        $paragraphs[$paragraph->id] = $paragraph;
                    }

                    $this->paragraphs = $paragraphs;
                }
            }
        },

        #[ORM\Column(type: Types::TEXT)]
        public ?string $description = null {
            #[Deprecated]
            get => $this->description;
            #[Deprecated]
            set => $value;
        },

        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
        public ?Scene $scene = null {
            get => $this->scene;
            set => $value;
        },

        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public array $actionGroups = [] {
            get => $this->actionGroups;
            set => $value;
        },

        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $attachments = null {
            get => $this->attachments;
            set {
                if (!is_null($value)) {
                    foreach ($value as $attachment) {
                        if (!$attachment["attachment"] instanceof Attachment) {
                            throw new TypeError("Attachment argument must be an instance of Attachment");
                        }
                    }
                }

                $this->attachments = $value;
            }
        },

        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $context = null {
            #[Deprecated]
            get => $this->context;
            #[Deprecated]
            set => $value;
        },
    ) {

    }

    /**
     * This preUpdate hook is necessary to make copies of all objects. Only then doctrine
     * realizes that these are new - and should be changed accordingly.
     * @return void
     */
    #[ORM\PreFlush()]
    public function preUpdate(): void
    {
        $this->actionGroups = array_map(fn ($x) => clone $x, $this->actionGroups);
    }

    #[Deprecated]
    public function addDescription(string $description, string $prefix = "\n\n"): static
    {
        $this->description .= $prefix . $description;
        return $this;
    }

    public function addActionGroup(ActionGroup $actionGroup): self
    {
        $actionGroups = $this->actionGroups;
        $actionGroups[$actionGroup->getId()] = $actionGroup;
        $this->actionGroups = $actionGroups;
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
        $attachments = $this->attachments;
        $attachments[] = [
            "attachment" => $attachment,
            "config" => $config,
            "data" => $data,
        ];
        $this->attachments = $attachments;
        return $this;
    }

    public function clearAttachments(): self
    {
        $this->attachments = [];
        return $this;
    }

    #[Deprecated]
    public function addContext(string $key, mixed $value): self
    {
        if (!is_array($this->context)) {
            $this->context = [];
        }

        $context = $this->context;
        $context[$key] = $value;

        $this->context = $context;
        return $this;
    }

    #[Deprecated]
    public function clearContext(): self
    {
        $this->context = [];
        return $this;
    }

    public function addParagraph(Paragraph $paragraph): self
    {
        $this->paragraphs = array_merge($this->paragraphs, [$paragraph->id => $paragraph]);

        return $this;
    }

    public function clearParagraphs(): self
    {
        $this->paragraphs = [];

        return $this;
    }

    public function clear(): self
    {
        $this->clearParagraphs();
        $this->clearContext();
        $this->clearAttachments();
        $this->clearActionGroups();

        return $this;
    }
}
