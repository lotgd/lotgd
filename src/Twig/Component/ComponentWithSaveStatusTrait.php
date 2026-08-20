<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait ComponentWithSaveStatusTrait
{
    #[LiveProp]
    public ?bool $saved = null;

    public function __invoke(): void
    {
        $this->saved = false;
    }
}