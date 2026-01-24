<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Entity\ActionGroup;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use LotGD2\Repository\SceneRepository;
use ValueError;

#[ORM\Entity(repositoryClass: SceneRepository::class)]
class Scene
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    /** @var Collection<int, SceneActionGroup>  */
    #[ORM\OneToMany(targetEntity: SceneActionGroup::class, mappedBy: 'scene', cascade: ["persist", "remove"], orphanRemoval: true)]
    #[ORM\OrderBy(["sorting" => "ASC"])]
    public Collection $actionGroups {
        get {
            return $this->actionGroups;
        }
        /** @var iterable<int, SceneActionGroup> $actionGroups */
        set(iterable $actionGroups) {
            $this->actionGroups = new ArrayCollection();
            foreach ($actionGroups as $actionGroup) {
                $this->addActionGroup($actionGroup);
            }
        }
    }

    /** @var Collection<int, SceneConnection>  */
    #[ORM\OneToMany(targetEntity: SceneConnection::class, mappedBy: 'sourceScene', cascade: ["persist", "remove"], orphanRemoval: true)]
    public Collection $sourcedConnections {
        get => $this->sourcedConnections;

        set(iterable $value) {
            $this->sourcedConnections = new ArrayCollection();
            foreach ($value as $sourcedConnection) {
                $this->addSourcedConnection($sourcedConnection);
            }
        }
    }

    /** @var Collection<int, SceneConnection>  */
    #[ORM\OneToMany(targetEntity: SceneConnection::class, mappedBy: 'targetScene', cascade: ["persist", "remove"], orphanRemoval: true)]
    public Collection $targetingConnections {
        get => $this->targetingConnections;
        set(iterable $value) {
            $this->targetingConnections = new ArrayCollection();
            foreach ($value as $targetingConnection) {
                $this->addTargetingConnection($targetingConnection);
            }
        }
    }

    /**
     * @param iterable<int, SceneConnection> $sourcedConnections
     * @param iterable<int, SceneConnection> $targetingConnections
     * @param iterable<int, SceneActionGroup> $actionGroups
     */
    public function __construct(
        #[ORM\Column(length: 255)]
        public ?string $title = null,

        #[ORM\Column(type: Types::TEXT)]
        public ?string $description = null,

        /** @var string|null  */
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $templateClass = null {
            get => $this->templateClass;
            set(?string $value) {
                if (!is_null($value) and !is_subclass_of($value, SceneTemplateInterface::class, true)) {
                    throw new ValueError("The template class of a scene must implement ".SceneTemplateInterface::class.", {$value} given.");
                }

                $this->templateClass = $value;
            }
        },

        /** @var null|array<string, mixed>  */
        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $templateConfig = [] {
            get => $this->templateConfig;
            set {
                if ($this->templateClass and is_subclass_of($this->templateClass, SceneTemplateInterface::class, true)) {
                    $this->templateConfig = $this->templateClass::validateConfiguration($value);
                } else {
                    $this->templateConfig = $value;
                }
            }
        },

        iterable $sourcedConnections = new ArrayCollection(),
        iterable $targetingConnections = new ArrayCollection(),
        iterable $actionGroups = new ArrayCollection(),

        #[ORM\Column(type: 'boolean', nullable: true, options: ["default" => false])]
        public ?bool $defaultScene = false,
    ) {
        $this->actionGroups = $actionGroups;
        $this->sourcedConnections = $sourcedConnections;
        $this->targetingConnections = $targetingConnections;
    }

    public function connectTo(
        Scene $scene,
        SceneConnectionType $connectionType = SceneConnectionType::BothWays,
        ?string $sourceLabel = null,
        ?string $targetLabel = null,
    ): SceneConnection {
        $connection = new SceneConnection(
            sourceScene: $this,
            targetScene: $scene,
            sourceLabel: $sourceLabel,
            targetLabel: $targetLabel,
            type: $connectionType,
        );

        $this->addSourcedConnection($connection);
        $scene->addTargetingConnection($connection);
        return $connection;
    }

    public function addSourcedConnection(SceneConnection $sourcedConnection): static
    {
        if (!$this->sourcedConnections->contains($sourcedConnection)) {
            $this->sourcedConnections->add($sourcedConnection);
            $sourcedConnection->sourceScene = $this;
        }

        return $this;
    }

    public function removeSourcedConnection(SceneConnection $sourcedConnection): static
    {
        if ($this->sourcedConnections->removeElement($sourcedConnection)) {
            // set the owning side to null (unless already changed)
            if ($sourcedConnection->sourceScene === $this) {
                $sourcedConnection->sourceScene = null;
            }
        }

        return $this;
    }

    public function addTargetingConnection(SceneConnection $targetingConnection): static
    {
        if (!$this->targetingConnections->contains($targetingConnection)) {
            $this->targetingConnections->add($targetingConnection);
            $targetingConnection->targetScene = $this;
        }

        return $this;
    }

    public function removeTargetingConnection(SceneConnection $targetingConnection): static
    {
        if ($this->targetingConnections->removeElement($targetingConnection)) {
            // set the owning side to null (unless already changed)
            if ($targetingConnection->targetScene === $this) {
                $targetingConnection->targetScene = null;
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
                    $connection->type == SceneConnectionType::BothWays or
                    $connection->type == SceneConnectionType::ForwardOnly
                ))->toArray(),
                ...$this->targetingConnections->filter(fn (SceneConnection $connection) => (
                    $connection->type == SceneConnectionType::BothWays or
                    $connection->type == SceneConnectionType::ReverseOnly
                ))->toArray()
            ]);
        } else {
            return new ArrayCollection([
                ...$this->sourcedConnections->toArray(),
                ...$this->targetingConnections->toArray()
            ]);
        }
    }

    public function addActionGroup(SceneActionGroup $actionGroup): static
    {
        if (!$this->actionGroups->contains($actionGroup)) {
            $this->actionGroups->add($actionGroup);
            $actionGroup->scene = $this;
        }

        return $this;
    }

    public function removeActionGroup(SceneActionGroup $actionGroup): static
    {
        if ($this->actionGroups->removeElement($actionGroup)) {
            // set the owning side to null (unless already changed)
            if ($actionGroup->scene === $this) {
                $actionGroup->scene = null;
            }
        }

        return $this;
    }

    public function isDefaultScene(): bool
    {
        return $this->defaultScene === true;
    }
}
