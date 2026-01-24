<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Repository\SceneActionGroupRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SceneActionGroupRepository::class)]
class SceneActionGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get => $this->id;
    }

    /** @var Collection<int, SceneConnection>  */
    #[ORM\ManyToMany(targetEntity: SceneConnection::class)]
    public Collection $connections {
        get => $this->connections;
        set(iterable $value) {
            $this->connections = new ArrayCollection();
            foreach ($value as $connection) {
                $this->addConnection($connection);
            }
        }
    }

    /**
     * @param iterable<int, SceneConnection> $connections
     */
    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank()]
        public ?string $title = null {
            get => $this->title;
            set => $value;
        },
        iterable $connections = [],

        #[ORM\ManyToOne(inversedBy: 'actionGroups')]
        #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
        public ?Scene $scene = null {
            get => $this->scene;
            set => $value;
        },

        #[ORM\Column(type: Types::SMALLINT)]
        #[Assert\NotNull()]
        public int $sorting = 0 {
            get => $this->sorting;
            set => $value ?? 0;
        },
    ) {
        $this->connections = $connections;
    }

    public function addConnection(SceneConnection $connection): static
    {
        if (!$this->connections->contains($connection)) {
            $this->connections->add($connection);
        }

        return $this;
    }

    public function removeConnection(SceneConnection $connection): static
    {
        $this->connections->removeElement($connection);

        return $this;
    }
}
