<?php
declare(strict_types=1);

namespace LotGD2\Entity\DataObject;

use Symfony\Component\Validator\Constraints\Range;

class Chance
{
    public function __construct(
        #[Range(min: 0)]
        public null|int $numerator {
            get => $this->numerator;
            set => $value;
        },

        #[Range(min: 1)]
        public null|int $denominator = 100 {
            get => $this->denominator;
            set => $value;
        },
    ) {

    }

    public function chance(): float
    {
        return ($this->numerator ?? 0) / ($this->denominator ?? 1);
    }

    public function precision(): int
    {
        return (int)ceil(log10($this->denominator ?? 1));
    }
}