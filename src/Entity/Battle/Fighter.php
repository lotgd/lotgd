<?php
declare(strict_types=1);

namespace LotGD2\Entity\Battle;

class Fighter implements FighterInterface
{
    public int|null $maxHealth {
        get {
            return $this->maxHealth;
        }
        set {
            $this->maxHealth = $value;
        }
    }

    /**
     * @var array<string, mixed>
     */
    public array $kwargs = [];

    public function __construct(
        public null|string $name {
            get {
                return $this->name;
            }
            set {
                $this->name = $value;
            }
        },

        public int|null $level {
            get {
                return $this->level;
            }
            set {
                $this->level = $value;
            }
        },

        public ?string $weapon {
            get {
                return $this->weapon;
            }
            set {
                $this->weapon = $value;
            }
        },

        public int|null $health {
            get {
                return $this->health;
            }
            set {
                $this->health = $value;
            }
        },

        public int|null $attack {
            get {
                return $this->attack;
            }
            set {
                $this->attack = $value;
            }
        },

        public int|null $defense {
            get {
                return $this->defense;
            }
            set {
                $this->defense = $value;
            }
        },

        mixed ... $kwargs,
    ) {
        $this->maxHealth = $health;
        $this->kwargs = $kwargs;
    }

    public function damage(int $damage): static
    {
        $this->health -= $damage;
        return $this;
    }
}