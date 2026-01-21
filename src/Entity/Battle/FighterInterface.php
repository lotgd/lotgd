<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use Symfony\Component\Serializer\Attribute\Groups;

interface FighterInterface extends BasicFighterInterface
{
    #[Groups("fighter")]
    public(set) ?int $maxHealth {
        get;
        set;
    }

    /**
     * @var array<string, mixed>
     */
    #[Groups("fighter")]
    public array $kwargs {
        get;
        set;
    }

    public function damage(int $damage): static;
}