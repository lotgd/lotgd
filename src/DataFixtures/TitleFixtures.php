<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Entity\Mapped\Title;
use Psr\Log\LoggerInterface;

final class TitleFixtures extends Fixture
{
    use TsvDataReaderTrait;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $fileName = implode(DIRECTORY_SEPARATOR, [__DIR__, "data", "titles.tsv"]);
        $statistics = [];
        $titlesAdded = 0;

        foreach ($this->iterateTsvData($fileName) as $row) {
            $title = new Title(
                dk: (int) $row["dk"],
                male: $row["male"],
                female: $row["female"],
                other: $row["other"],
            );

            $manager->persist($title);
            $titlesAdded++;

            $this->logger->debug("Adds Title for dk={$title->dk}: {$title->male}/{$title->female}/{$title->other}");

            if (isset($statistics[$title->dk])) {
                $this->logger->warning("There is already a title for dk={$title->dk}");
            }

            $statistics[$title->dk] = true;
        }

        if ($titlesAdded === 0) {
            $this->logger->warning("There where no titles added");
        }

        if ($titlesAdded > count($statistics)) {
            $this->logger->warning("Number of titles added is larger than levels are covered: Duplicates exist.");
        }

        if ($titlesAdded <= max(array_keys($statistics))) {
            $this->logger->warning("Number of titles added is smaller than highest level: There are gaps.");
        }

        $manager->flush();
    }
}