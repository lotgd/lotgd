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
            ),
            new Race(
                name: "Dwarf",
                description: <<<TEXT
                    You are from the depths of subterranean strongholds of Qexelcrag, home to the noble and fierce Dwarven 
                    people whose desire for privacy and treasure bears no resemblance to their tiny stature.
                    TEXT,
                selectionText: <<<TXT
                    As a dwarf, you are more easily able to identify the value of certain goods.
                    
                    You gain extra gold from forest fights!
                    TXT,
                className: StandardRace::class,
                configuration: [
                    "goldFactor" => 1.2,
                ],
            ),
            new Race(
                name: "Elf",
                description: <<<TEXT
                    You grew up high among the trees of the Glorfindal forest, in frail looking elaborate Elvish structures 
                    that look as though they might collapse under the slightest strain, yet have existed for centuries.
                    TEXT,
                selectionText: <<<TXT
                    As an elf, you are keenly aware of your surroundings at all times; very little ever catches you by surprise.
                    
                    You gain extra defense!
                    TXT,
                className: StandardRace::class,
                configuration: [
                    "defense" => 1,
                ],
            ),
            new Race(
                name: "Troll",
                description: <<<TEXT
                    You are from the swamps of Glukmoore as a Troll, fending for yourself from the very moment you crept 
                    out of your leathery egg, slaying your yet unhatched siblings, and feasting on their bones.
                    TEXT,
                selectionText: <<<TXT
                    As a troll, and having always fended for yourself, the ways of battle are not foreign to you.
                    
                    You gain extra attack!!
                    TXT,
                className: StandardRace::class,
                configuration: [
                    "attack" => 1,
                ],
            ),
            new Race(
                name: "Lizard",
                description: <<<TEXT
                    You hatched from your egg in a hole somewhere on a barren field, beyond any civilization. 
                    Related to dragons, you have a hard live.
                    TEXT,
                selectionText: <<<TXT
                    As a lizard, your regular skin shedding give you a substantial advantage for your health compared to other races.
                    
                    You start with more health!
                    TXT,
                className: StandardRace::class,
                configuration: [
                    "health" => 2,
                ],
            ),
        ];

        array_map(fn (Race $race) => $manager->persist($race), $races);
        $manager->flush();
    }
}