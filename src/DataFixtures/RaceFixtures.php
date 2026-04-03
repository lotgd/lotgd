<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Race;
use LotGD2\Game\Race\StandardRace;

class RaceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $races = [
            new Race(
                name: "Human",
                description: <<<TEXT
                    You grew up on the plains around the city of Romar, the city of men; always following your father and looking up to 
                    his every move, until he sought out the Green Dragon, never to be seen again.
                    TEXT,
                selectionText: <<<TXT
                    As a human, your size and strength permit you the ability to effortlessly wield weapons, tiring much 
                    less quickly than other races.
                    
                    You gain one extra turn each day!
                    TXT,
                className: StandardRace::class,
                configuration: [
                    "turns" => 1,
                ],
            )
        ];

        array_map(fn (Race $race) => $manager->persist($race), $races);
        $manager->flush();
    }
}