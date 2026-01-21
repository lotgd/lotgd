<?php
declare(strict_types=1);

namespace LotGD2\Game\Enum;

enum SceneConnectionType: string
{
    case BothWays = "BothWays";
    case ForwardOnly = "FowardOnly";
    case ReverseOnly = "ReverseOnly";
}
