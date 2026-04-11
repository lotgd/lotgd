<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use LotGD2\Game\Race\RaceInterface;
use LotGD2\Repository\RaceRepository;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template TConfig of array<string, mixed> = array<string, mixed>
 */
#[ORM\Entity(repositoryClass: RaceRepository::class)]
#[ORM\Index(fields: ["name"])]
class Race implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private(set) ?int $id = null;

    /**
     * @param string|null $name
     * @param string|null $description
     * @param string|null $selectionText
     * @param null|class-string<RaceInterface<TConfig>> $className
     * @param array $configuration
     */
    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank()]
        public ?string $name = null {
            get => $this->name;
            set => $value;
        },

        #[ORM\Column(type: Types::TEXT)]
        #[Assert\NotBlank()]
        public ?string $description = null {
            get => $this->description;
            set => $value;
        },

        #[ORM\Column(type: Types::TEXT)]
        #[Assert\NotBlank()]
        public ?string $selectionText = null {
            get => $this->selectionText;
            set => $value;
        },

        // @phpstan-ignore doctrine.columnType
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank()]
        public ?string $className = null {
            get => $this->className;
            set => $value;
        },

        /**
         * @var TConfig
         */
        #[ORM\Column(type: Types::JSONB)]
        #[Assert\NotBlank()]
        public array $configuration = [] {
            get => $this->configuration;
            set => $value;
        }
    ) {

    }

    public function __toString(): string
    {
        return "<Character#{$this->id} {$this->name}>";
    }
}