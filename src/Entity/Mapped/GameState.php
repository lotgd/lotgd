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
         * @var array<string, mixed>|null
         */
        #[ORM\Column(type: JsonDocumentType::NAME, nullable: true)]
        public mixed $state = [] {
            get {
                if (is_null($this->state)) {
                    return null;
                } elseif (array_key_exists("__", $this->state)) {
                    return $this->state["__"];
                } else {
                    return $this->state;
                }
            }

            set(mixed $value) {
                if (is_array($value)) {
                    $this->state = $value;
                } else {
                    $this->state = [
                        "__" => $value,
                    ];
                }
            }
        },

        #[ORM\Column(type: Types::STRING, nullable: false, enumType: GameStateType::class)]
        public GameStateType $type = GameStateType::Setting {
            get => $this->type;
            set => $value;
        }
    ) {

    }
}