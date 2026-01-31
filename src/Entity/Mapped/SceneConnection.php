<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Repository\SceneConnectionRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use ValueError;

#[ORM\Entity(repositoryClass: SceneConnectionRepository::class)]
class SceneConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get => $this->id;
    }

    /**
     * @var Collection<int, SceneActionGroup>
     */
    #[ORM\ManyToMany(targetEntity: SceneActionGroup::class, mappedBy: "connections")]
    private Collection $sceneActionGroup;

    public ?SceneActionGroup $sourceActionGroup {
        get => $this->_getSourceActionGroup();

        set(?SceneActionGroup $value) {
            // No matter what $value is, we need to remote the existing reference to this connection
            $this->_getSourceActionGroup()?->removeConnection($this);

            if (is_null($value)) {
                // Value is none, our job is done.
                return;
            }

            if ($value->scene !== $this->sourceScene) {
                // Make sure we don't just set any scene connection
                throw new ValueError("The SceneActionGroup must belong to the same scene as this connection's source scene.");
            }

            // Add this connection to the new action group
            $value->addConnection($this);
        }
    }

    public ?SceneActionGroup $targetActionGroup {
        get => $this->_getTargetActionGroup();

        set(?SceneActionGroup $value) {
            // No matter what $value is, we need to remote the existing reference to this connection
            $this->_getTargetActionGroup()?->removeConnection($this);

            if (is_null($value)) {
                // Value is none, our job is done.
                return;
            }

            if ($value->scene !== $this->targetScene) {
                // Make sure we don't just set any scene connection
                throw new ValueError("The SceneActionGroup must belong to the same scene as this connection's target scene.");
            }

            // Add this connection to the new action group
            $value->addConnection($this);
        }
    }

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'sourcedConnections')]
        #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
        #[Assert\NotNull]
        public ?Scene $sourceScene = null,

        #[ORM\ManyToOne(inversedBy: 'targetingConnections')]
        #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
        #[Assert\NotNull]
        public ?Scene $targetScene = null,

        #[ORM\Column(length: 255, nullable: true)]
        #[Length(max: 255)]
        public ?string $sourceLabel = null,

        #[ORM\Column(length: 255, nullable: true)]
        #[Length(max: 255)]
        public ?string $targetLabel = null,

        #[ORM\Column(length: 255, enumType: SceneConnectionType::class)]
        public ?SceneConnectionType $type = SceneConnectionType::BothWays,

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\ExpressionSyntax()]
        #[Length(max: 255)]
        public ?string $sourceExpression = null {
            get {
                return $this->sourceExpression ?? null;
            }
            set {
                $this->sourceExpression = $value;
            }
        },

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\ExpressionSyntax()]
        #[Length(max: 255)]
        public ?string $targetExpression = null {
            get {
                return $this->targetExpression ?? null;
            }
            set {
                $this->targetExpression = $value;
            }
        },
    ) {
        $this->sceneActionGroup = new ArrayCollection();
    }

    private function _getTargetActionGroup(): ?SceneActionGroup
    {
        return $this->sceneActionGroup->findFirst(function (int $key, SceneActionGroup $sceneActionGroup): bool {
            if ($sceneActionGroup->scene === $this->targetScene and $sceneActionGroup->connections->contains($this)) {
                return true;
            }

            return false;
        });
    }

    private function _getSourceActionGroup(): ?SceneActionGroup
    {
        return $this->sceneActionGroup->findFirst(function (int $key, SceneActionGroup $sceneActionGroup): bool {
            if ($sceneActionGroup->scene === $this->sourceScene and $sceneActionGroup->connections->contains($this)) {
                return true;
            }

            return false;
        });
    }
}
