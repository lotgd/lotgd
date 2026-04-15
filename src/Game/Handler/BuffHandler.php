<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BuffHandler
{
    public function __construct(
        private ?LoggerInterface $logger = null,
        private ?DiceBagInterface $diceBag = null,
    ) {

    }

    public function getBuffs(Character|FighterInterface $fighter): BuffList
    {
        if ($fighter instanceof Character) {
            $buffs = $fighter->getProperty("buffs", []);
        } else {
            $buffs = $fighter->kwargs["buffs"] ?? [];
        }

        return new BuffList(
            $this->logger ?? new NullLogger(),
            $this->diceBag ?? new DiceBag(),
            $buffs
        );
    }

    /**
     * @param Buff[] $buffs
     * @return void
     */
    public function setBuffs(Character|FighterInterface $fighter, BuffList $buffs)
    {
        if ($fighter instanceof Character) {
            $fighter->setProperty("buffs", $buffs->buffs);
        } else {
            $fighter->kwargs["buffs"] = $buffs->buffs;
        }
    }
}