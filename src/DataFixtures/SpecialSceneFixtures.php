<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Scene\SceneTemplate\Special\StumbleSpecialTemplate;
use LotGD2\Game\Scene\SceneTemplate\SpecialTemplate;

class SpecialSceneFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $scenes = [
            "stumble" => new Scene(
                title: "You stumbled!",
                description: <<< TXT
                    You heard something weird. You look up and don't check your surroundings. Your foot got caught
                    in something, and you stumble.
                    TXT,
                templateClass: StumbleSpecialTemplate::class,
                tags: [SpecialTemplate::SceneTag],
            ),
        ];

        foreach ($scenes as $scene) {
            $manager->persist($scene);
        }

        $manager->flush();
    }
}