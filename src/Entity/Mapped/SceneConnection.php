<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\ORM\Mapping as ORM;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Repository\SceneConnectionRepository;

#[ORM\Entity(repositoryClass: SceneConnectionRepository::class)]
class SceneConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sourcedConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Scene $sourceScene = null;

    #[ORM\ManyToOne(inversedBy: 'targetingConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Scene $targetScene = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetLabel = null;

    #[ORM\Column(length: 255, enumType: SceneConnectionType::class)]
    private ?SceneConnectionType $type = SceneConnectionType::BothWays;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceScene(): ?Scene
    {
        return $this->sourceScene;
    }

    public function setSourceScene(?Scene $sourceScene): static
    {
        $this->sourceScene = $sourceScene;

        return $this;
    }

    public function getTargetScene(): ?Scene
    {
        return $this->targetScene;
    }

    public function setTargetScene(?Scene $targetScene): static
    {
        $this->targetScene = $targetScene;

        return $this;
    }

    /**
     * Returns the label visible on the source scene
     * @return string|null
     */
    public function getSourceLabel(): ?string
    {
        return $this->sourceLabel;
    }

    public function setSourceLabel(?string $sourceLabel): static
    {
        $this->sourceLabel = $sourceLabel;

        return $this;
    }

    /**
     * Returns the label visible on the target scene
     * @return string|null
     */
    public function getTargetLabel(): ?string
    {
        return $this->targetLabel;
    }

    public function setTargetLabel(?string $targetLabel): static
    {
        $this->targetLabel = $targetLabel;

        return $this;
    }

    public function getType(): ?SceneConnectionType
    {
        return $this->type;
    }

    public function setType(SceneConnectionType $type): static
    {
        $this->type = $type;

        return $this;
    }
}
