<?php
declare(strict_types=1);

namespace LotGD2\Game\Random;

// @phpstan-ignore trait.unused
trait DiceBagAwareTrait
{
    public DiceBagInterface $diceBag {
        get {
            if (!isset($this->diceBag)) {
                $this->diceBag = new DiceBag();
            }

            return $this->diceBag;
        }
        set => $value;
    }
}