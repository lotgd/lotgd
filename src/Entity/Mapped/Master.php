<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Entity\Battle\BasicFighterInterface;
use LotGD2\Repository\MasterRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MasterRepository::class)]
#[ORM\Index(fields: ["level"])]
class Master implements BasicFighterInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private(set) ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank]
        public ?string $name = null,

        #[ORM\Column(type: Types::SMALLINT)]
        #[Assert\Range(min: 1, max: 255)]
        public ?int $level = null,

        #[ORM\Column(length: 255)]
        #[Assert\NotBlank]
        public ?string $weapon = null,

        #[ORM\Column]
        #[Assert\NotBlank]
        public ?int $health = null,

        #[ORM\Column]
        #[Assert\NotBlank]
        public ?int $attack = null,

        #[ORM\Column]
        #[Assert\NotBlank]
        public ?int $defense = null,

        #[ORM\Column(length: 255)]
        #[Assert\NotBlank]
        #[Groups("fighter")]
        public ?string $textDefeated = null,

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\NotBlank()]
        #[Groups("fighter")]
        public ?string $textLost = null,
    ) {

    }
}