<?php
declare(strict_types=1);

namespace LotGD2\Form\Race;

use LotGD2\Form\GroupedFormType;
use LotGD2\Game\Race\StandardRace;
use Symfony\Component\Form\AbstractType;

/**
 * @phpstan-import-type StandardRaceConfiguration from StandardRace
 * @extends AbstractType<StandardRaceConfiguration>
 */
class StandardRaceType extends AbstractType
{
    public function getParent(): string
    {
        return GroupedFormType::class;
    }
}