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
    const string BuffPropertyName = "buffs";

    public function __construct(
        private ?LoggerInterface $logger = null,
        private ?DiceBagInterface $diceBag = null,
    ) {

    }

    public function getBuffs(Character|FighterInterface $fighter): BuffList
    {
        if ($fighter instanceof Character) {
            $buffs = $fighter->getProperty(self::BuffPropertyName, []);

            $this->logger->debug("{$fighter}: Returns new BuffList");
        } else {
            $buffs = $fighter->kwargs[self::BuffPropertyName] ?? [];
        }

        return new BuffList(
            $this->logger ?? new NullLogger(),
            $this->diceBag ?? new DiceBag(),
            $buffs
        );
    }

    /**
     * @param Character|FighterInterface $fighter
     * @param BuffList $buffs
     * @return void
     */
    public function setBuffs(Character|FighterInterface $fighter, BuffList $buffs): void
    {
        $oldLength = count($this->getBuffs($fighter)->buffs);
        $newLength = count($buffs->buffs);
        if ($fighter instanceof Character) {
            $this->logger->debug("{$fighter}: Set BuffList (New length: {$newLength}, Old length: {$oldLength})");
            $fighter->setProperty(self::BuffPropertyName , $buffs->buffs);
        } else {
            $fighter->kwargs[self::BuffPropertyName] = $buffs->buffs;
        }
    }

    public function addBuff(Character|FighterInterface $fighter, Buff $buff): void
    {
        if ($fighter instanceof Character) {
            $fighter->setProperty(self::BuffPropertyName, array_merge($fighter->getProperty(self::BuffPropertyName, []), [$buff]));
        } else {
            $fighter->kwargs[self::BuffPropertyName][] = $buff;
        }
    }
}