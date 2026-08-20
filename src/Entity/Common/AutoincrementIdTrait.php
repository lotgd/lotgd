<?php
declare(strict_types=1);

namespace LotGD2\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

trait AutoincrementIdTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null {
        get => $this->id;
    }
}
