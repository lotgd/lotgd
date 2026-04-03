<?php
declare(strict_types=1);

namespace LotGD2\Game\Race;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Race;

/**
 * @template TConfig of array<string, mixed> = array<string, mixed>
 */
interface RaceInterface
{
    /**
     * @param Character $character
     * @param Race<covariant TConfig> $race
     * @return void
     */
    public function onSelect(Character $character, Race $race): void;

    /**
     * @param Character $character
     * @param Race<covariant TConfig> $race
     * @param TConfig $oldConfiguration
     * @return void
     */
    public function onDeselect(Character $character, Race $race, array $oldConfiguration): void;
}