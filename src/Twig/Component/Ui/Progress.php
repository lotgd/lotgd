<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Ui;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class Progress
{
    public ?string $label = null;
    public int|float $min = 0;
    public int|float $max;
    public int|float $current;
    public bool $colored = false;
    public ?string $barColor = null;
    public float $dangerThreshold = 0.25;
    public float $warningThreshold = 0.5;
    public bool $displayCurrent = false;
    public bool $displayMax = false;
    public float $barLength {
        get => max(0, min(1, $this->current / $this->max));
    }

    #[ExposeInTemplate]
    public function getWidth(): float
    {
        return $this->current / $this->max;
    }

    #[ExposeInTemplate]
    public function getColor(): string
    {
        $width = $this->getWidth();

        if ($this->colored) {
            return $width > $this->warningThreshold ? "bg-success" : ($width > $this->dangerThreshold ? "bg-alert" : "bg-danger");
        }

        if ($this->barColor !== null) {
            return $this->barColor;
        }

        return "";
    }

    #[ExposeInTemplate]
    public function getText(): string
    {
        if ($this->displayCurrent && $this->displayMax) {
            return "{$this->current} / {$this->max}";
        } elseif ($this->displayCurrent) {
            return "$this->current";
        } elseif ($this->displayMax) {
            return "/ {$this->max}";
        } else {
            return "";
        }
    }
}