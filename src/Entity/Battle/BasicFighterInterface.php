<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

use Symfony\Component\Serializer\Attribute\Groups;

interface BasicFighterInterface
{
    #[Groups("fighter")]
    public ?string $name {
        get;
        set;
    }

    #[Groups("fighter")]
    public ?int $level {
        get;
        set;
    }

    #[Groups("fighter")]
    public ?string $weapon {
        get;
        set;
    }

    #[Groups("fighter")]
    public ?int $health {
        get;
        set;
    }

    #[Groups("fighter")]
    public ?int $attack {
        get;
        set;
    }

    #[Groups("fighter")]
    public ?int $defense {
        get;
        set;
    }

    public function __construct(
        ?string $name,
        ?int $level,
        ?string $weapon,
        ?int $health,
        ?int $attack,
        ?int $defense,
    );
}