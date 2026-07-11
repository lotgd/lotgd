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
            "stumbleBearTrap" => new Scene(
                title: "You stumbled!",
                description: <<< TXT
                    You are striving through the forest, only a tiny bit less careful than usually. And - of course! - 
                    you step in a bear trap. You can free yourself, but the wound is deep.
                    
                    You lose {{ damage }} health points!
                    TXT,
                templateClass: StumbleSpecialTemplate::class,
                templateConfig: [
                    "chance" => 100,
                    "minDamage" => "character.level*2",
                    "maxDamage" => "character.level*5",
                    "playerCanDie" => true,
                ],
                tags: [SpecialTemplate::SceneTag]
            ),
            "stumbleFall" => new Scene(
                title: "You stumbled!",
                description: <<< TXT
                    What bad luck! You slip on something and hurt yourself as you fall.
                    You notice a smell like manure. Horse manure!
                    
                    You lose {{ damage }} health points!
                    TXT,
                templateClass: StumbleSpecialTemplate::class,
                templateConfig: [
                    "chance" => 100,
                    "minDamage" => "character.level*1",
                    "maxDamage" => "character.level*2",
                    "playerCanDie" => false,
                ],
                tags: [SpecialTemplate::SceneTag],
            ),
            "stumbleOldMan" => new Scene(
                title: "You stumbled!",
                description: <<< TXT
                    {% if somethingHappened %}
                        An old man hits you violently with a cane, giggles, and runs away.
                        
                        You lose {{ damage }} health points!
                    {% else %}
                        An old man tries to hit you with his cane, but he misses.
                        He... grunts disappointed and runs away.
                    {% endif %}
                    TXT,
                templateClass: StumbleSpecialTemplate::class,
                templateConfig: [
                    "chance" => 50,
                    "minDamage" => "character.level",
                    "maxDamage" => "character.level*3",
                    "playerCanDie" => true,
                ],
                tags: [SpecialTemplate::SceneTag],
            ),
        ];

        foreach ($scenes as $scene) {
            $manager->persist($scene);
        }

        $manager->flush();
    }
}