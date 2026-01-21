<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\ORM\Mapping as ORM;
use LotGD2\Game\Enum\SceneConnectionType;
use LotGD2\Repository\SceneConnectionRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SceneConnectionRepository::class)]
class SceneConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get => $this->id;
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
        public ?string $sourceLabel = null,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $targetLabel = null,

        #[ORM\Column(length: 255, enumType: SceneConnectionType::class)]
        public ?SceneConnectionType $type = SceneConnectionType::BothWays,
    ) {

    }
}
