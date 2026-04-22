<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;

class SpecialtyHandler
{
    public function __construct(
        private ?LoggerInterface $logger = null, // @phpstan-ignore property.onlyWritten
        private ?DiceBagInterface $diceBag = null, // @phpstan-ignore property.onlyWritten
    ) {

    }
}