<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Entity\Mapped\Master;
use Psr\Log\LoggerInterface;

class MasterFixtures extends Fixture
{
    use TsvDataReaderTrait;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $fileName = implode(DIRECTORY_SEPARATOR, [__DIR__, "data", "masters.tsv"]);
        $statistics = [];

        foreach ($this->iterateTsvData($fileName) as $row) {
            $master = new Master(
                name: $row["name"],
                level: (int)$row["level"],
                weapon: $row["weapon"],
                health: (int)$row["health"],
                attack: (int)$row["attack"],
                defense: (int)$row["defense"],
                textDefeated: $row["textDefeated"],
                textLost: $row["textLost"],
            );

            $manager->persist($master);

            if (!isset($statistics[$row["level"]])) {
                $statistics[$row["level"]] = 0;
            }

            $statistics[$row["level"]] += 1;

            $this->logger->debug("Adds master {$row['name']}'");
        }

        ksort($statistics);

        foreach ($statistics as $level => $count) {
            $this->logger->notice("Added {$count} master of level {$level}");
        }

        $manager->flush();
    }
}