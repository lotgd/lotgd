<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Creature;
use Psr\Log\LoggerInterface;
use SplFileObject;

final class CreatureFixtures extends Fixture
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $file = new SplFileObject(implode(DIRECTORY_SEPARATOR, [__DIR__, "data", "creatures.tsv"]));
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl("\t");

        $statistics = [];

        $header = null;
        foreach ($file as $row) {
            if (!$header) {
                $header = $row;
                continue;
            }

            // Reached end of file
            if (count($row) === 1) {
                break;
            }

            try {
                $row = array_combine($header, $row);
                $row = array_map('trim', $row);
                $row = array_map(fn ($x) => $x === "NULL" ? NULL : $x, $row);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), $row);
                continue;
            }

            $creature = new Creature(
                name: $row["name"],
                level: (int) $row["level"],
                weapon: $row["weapon"],
                textDefeated: $row["textDefeated"],
                textLost: $row["textLost"],
                gold: (int) $row["gold"],
                experience: (int) $row["experience"],
                health: (int) $row["health"],
                attack: (int) $row["attack"],
                defense: (int) $row["defense"],
                credits: $row["credits"],
            );

            $manager->persist($creature);

            if (!isset($statistics[$row["level"]])) {
                $statistics[$row["level"]] = 0;
            }

            $statistics[$row["level"]] += 1;

            $this->logger->debug("Adds creature {$row['name']}'");
        }

        ksort($statistics);

        foreach ($statistics as $level => $count) {
            $this->logger->notice("Added {$count} creatures of level {$level}");
        }

        $manager->flush();
    }
}