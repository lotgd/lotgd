<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Game\Enum\GameStateType;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class GameState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private(set) ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank()]
        public ?string $name = null {
            get => $this->name;
            set => $value;
        },

        /**
         * @var array<string, mixed>
         */
        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public ?array $state = [] {
            get => $this->state;
            set => $value;
        },

        #[ORM\Column(type: Types::STRING, nullable: false, enumType: GameStateType::class)]
        public GameStateType $type = GameStateType::Setting {
            get => $this->type;
            set => $value;
        }
    ) {

    }
}