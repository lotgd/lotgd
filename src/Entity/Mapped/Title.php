<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LotGD2\Entity\Common\AutoincrementIdTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Title
{
    use AutoincrementIdTrait;

    public function __construct(
        #[ORM\Column(type: Types::SMALLINT)]
        #[Assert\Range(min: 0, max: 32767)]
        public int $dk = 0 {
            get => $this->dk;
            set => $value;
        },
        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank()]
        #[Assert\Length(min: 1, max: 255)]
        public ?string $male = null {
            get => $this->male;
            set => $value;
        },
        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank()]
        #[Assert\Length(min: 1, max: 255)]
        public ?string $female = null {
            get => $this->female;
            set => $value;
        },
        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank()]
        #[Assert\Length(min: 1, max: 255)]
        public ?string $other = null {
            get => $this->other;
            set => $value;
        },
    ) {

    }
}
