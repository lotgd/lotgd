<?php
declare(strict_types=1);

namespace LotGD2\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Repository\SceneActionGroupRepository;

#[ORM\Entity(repositoryClass: SceneActionGroupRepository::class)]
class SceneActionGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /** @var Collection<int, SceneConnection>  */
    #[ORM\ManyToMany(targetEntity: SceneConnection::class)]
    private Collection $connections;

    #[ORM\ManyToOne(inversedBy: 'actionGroups')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Scene $scene = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $sorting = 0;

    public function __construct()
    {
        $this->connections = new ArrayCollection();
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

    /**
     * @return Collection<int, SceneConnection>
     */
    public function getConnections(): Collection
    {
        return $this->connections;
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

    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    public function setScene(?Scene $scene): static
    {
        $this->scene = $scene;

        return $this;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): static
    {
        $this->sorting = $sorting;

        return $this;
    }
}
