<?php
declare(strict_types=1);

namespace LotGD2\Entity\DataObject;

use Symfony\Component\Validator\Constraints as Assert;

class InnFlirtOption
{
    public function __construct(
        #[Assert\NotBlank]
        public ?int $charmRequirement,
        #[Assert\NotBlank()]
        public ?string $name,
        #[Assert\NotBlank()]
        public ?string $successMessage,
        #[Assert\NotBlank()]
        public ?string $failureMessage,
        #[Assert\NotBlank()]
        public ?bool $addCharmIfSuccessful = true,
        #[Assert\NotBlank()]
        public ?bool $removeCharmIfFailure = true,
        #[Assert\NotBlank()]
        public ?bool $isExhaustiveIfSuccessful = false,
        #[Assert\Valid]
        public ?ValueRange $charmRewardRange = null,
        #[Assert\Valid]
        public ?ValueRange $charmRemovalRange = null,
    ) {
        if ($this->charmRewardRange === null and $this->charmRequirement !== null) {
            $this->charmRewardRange = new ValueRange(0, $this->charmRequirement+2);
        }

        if ($this->charmRemovalRange === null and $this->charmRequirement !== null) {
            $this->charmRemovalRange = new ValueRange(0, $this->charmRequirement-1);
        }
    }
}
