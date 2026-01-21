<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use LotGD2\Repository\SceneRepository;

#[ORM\Entity(repositoryClass: SceneRepository::class)]
class Scene
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /** @var class-string<SceneTemplateInterface>|null  */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $templateClass = null;

    /** @var null|array<string, mixed>  */
    #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
    private ?array $templateConfig = null;

    /** @var Collection<int, SceneConnection>  */
    #[ORM\OneToMany(targetEntity: SceneConnection::class, mappedBy: 'sourceScene', cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $sourcedConnections;

    /** @var Collection<int, SceneConnection>  */
    #[ORM\OneToMany(targetEntity: SceneConnection::class, mappedBy: 'targetScene', cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $targetingConnections;

    /** @var Collection<int, SceneActionGroup>  */
    #[ORM\OneToMany(targetEntity: SceneActionGroup::class, mappedBy: 'scene', cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $actionGroups;

    #[ORM\Column(type: 'boolean', nullable: true, options: ["default" => false])]
    private ?bool $defaultScene = false;

    public function __construct()
    {
        $this->sourcedConnections = new ArrayCollection();
        $this->targetingConnections = new ArrayCollection();
        $this->actionGroups = new ArrayCollection();
        $this->templateConfig = [];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return class-string<SceneTemplateInterface>|null
     */
    public function getTemplateClass(): ?string
    {
        return $this->templateClass;
    }

    /**
     * @param class-string<SceneTemplateInterface>|null $templateClass
     * @return $this
     */
    public function setTemplateClass(?string $templateClass): static
    {
        if (!is_a($templateClass, SceneTemplateInterface::class, true)) {
            throw new \ValueError("The template class of a scene must implement ".SceneTemplateInterface::class.".");
        }

        $this->templateClass = $templateClass;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateConfig(): array
    {
        if (null === $this->templateConfig) {
            $this->templateConfig = [];
        }

        return $this->templateConfig;
    }

    /**
     * @param array<string, mixed> $templateConfig
     * @return $this
     */
    public function setTemplateConfig(array $templateConfig, bool $callValidation = true): static
    {
        if ($this->templateClass and $callValidation) {
            $this->templateConfig = $this->templateClass::validateConfiguration($templateConfig);
        } else {
            $this->templateConfig = $templateConfig;
        }

        return $this;
    }

    public function connectTo(
        Scene $scene,
        SceneConnectionType $connectionType = SceneConnectionType::BothWays,
        ?string $sourceLabel = null,
        ?string $targetLabel = null,
    ): SceneConnection {
        $connection = new SceneConnection()
            ->setSourceScene($this)
            ->setTargetScene($scene)
            ->setSourceLabel($sourceLabel)
            ->setTargetLabel($targetLabel)
            ->setType($connectionType);
        ;

        $this->addSourcedConnection($connection);
        $scene->addTargetingConnection($connection);
        return $connection;
    }

    /**
     * @return Collection<int, SceneConnection>
     */
    public function getSourcedConnections(): Collection
    {
        return $this->sourcedConnections;
    }

    public function addSourcedConnection(SceneConnection $sourcedConnection): static
    {
        if (!$this->sourcedConnections->contains($sourcedConnection)) {
            $this->sourcedConnections->add($sourcedConnection);
            $sourcedConnection->setSourceScene($this);
        }

        return $this;
    }

    public function removeSourcedConnection(SceneConnection $sourcedConnection): static
    {
        if ($this->sourcedConnections->removeElement($sourcedConnection)) {
            // set the owning side to null (unless already changed)
            if ($sourcedConnection->getSourceScene() === $this) {
                $sourcedConnection->setSourceScene(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SceneConnection>
     */
    public function getTargetingConnections(): Collection
    {
        return $this->targetingConnections;
    }

    public function addTargetingConnection(SceneConnection $targetingConnection): static
    {
        if (!$this->targetingConnections->contains($targetingConnection)) {
            $this->targetingConnections->add($targetingConnection);
            $targetingConnection->setTargetScene($this);
        }

        return $this;
    }

    public function removeTargetingConnection(SceneConnection $targetingConnection): static
    {
        if ($this->targetingConnections->removeElement($targetingConnection)) {
            // set the owning side to null (unless already changed)
            if ($targetingConnection->getTargetScene() === $this) {
                $targetingConnection->setTargetScene(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SceneConnection>
     */
    public function getConnections(
        bool $visibleOnly = false,
    ): Collection {
        if ($visibleOnly) {
            return new ArrayCollection([
                ...$this->sourcedConnections->filter(fn (SceneConnection $connection) => (
                    $connection->getType() == SceneConnectionType::BothWays or
                    $connection->getType() == SceneConnectionType::ForwardOnly
                ))->toArray(),
                ...$this->targetingConnections->filter(fn (SceneConnection $connection) => (
                    $connection->getType() == SceneConnectionType::BothWays or
                    $connection->getType() == SceneConnectionType::ReverseOnly
                ))->toArray()
            ]);
        } else {
            return new ArrayCollection([
                ...$this->sourcedConnections->toArray(),
                ...$this->targetingConnections->toArray()
            ]);
        }
    }

    /**
     * @return Collection<int, SceneActionGroup>
     */
    public function getActionGroups(): Collection
    {
        return $this->actionGroups;
    }

    public function addActionGroup(SceneActionGroup $actionGroup): static
    {
        if (!$this->actionGroups->contains($actionGroup)) {
            $this->actionGroups->add($actionGroup);
            $actionGroup->setScene($this);
        }

        return $this;
    }

    public function removeActionGroup(SceneActionGroup $actionGroup): static
    {
        if ($this->actionGroups->removeElement($actionGroup)) {
            // set the owning side to null (unless already changed)
            if ($actionGroup->getScene() === $this) {
                $actionGroup->setScene(null);
            }
        }

        return $this;
    }

    public function isDefaultScene(): bool
    {
        return $this->defaultScene === true;
    }

    public function setDefaultScene(bool $defaultScene): static
    {
        $this->defaultScene = $defaultScene;
        return $this;
    }
}
