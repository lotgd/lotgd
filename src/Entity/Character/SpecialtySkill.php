<?php
declare(strict_types=1);

namespace LotGD2\Entity\Character;

use LotGD2\Entity\Battle\ProtoBuff;

class SpecialtySkill
{
    public function __construct(
        protected(set) string $name,
        protected(set) int $costs,
        protected(set) ProtoBuff $buff,
    ) {

    }
}