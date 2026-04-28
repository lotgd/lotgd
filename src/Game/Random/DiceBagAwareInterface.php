<?php
declare(strict_types=1);

namespace LotGD2\Game\Random;

interface DiceBagAwareInterface
{
    public DiceBagInterface $diceBag {
        get;
        set;
    }
}