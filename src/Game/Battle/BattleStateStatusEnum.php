<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle;

enum BattleStateStatusEnum: int
{
    case Undecided = 0;
    case GoodGuyWon = 1;
    case BadGuyWon = 2;
}
