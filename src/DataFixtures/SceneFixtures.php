<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Scene;
use LotGD2\Entity\SceneActionGroup;
use LotGD2\Game\Scene\SceneTemplate\WeaponShopTemplate;

class SceneFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        /** @var Scene[] $scenes */
        $scenes = [
            "village" => (new Scene())
                ->setDefaultScene(true)
                ->setTitle("Village square")
                ->setDescription("People are busy going on errands. No one really sees you. There are different 
                    shops around the square. The village is surrounded by a deep, black forest.")
                ->addActionGroup(
                    (new SceneActionGroup())
                        ->setSorting(0)
                        ->setTitle("Outside")
                )
                ->addActionGroup(
                    (new SceneActionGroup())
                        ->setSorting(1)
                        ->setTitle("Blade alley")
                )
                ->addActionGroup(
                    (new SceneActionGroup())
                        ->setSorting(2)
                        ->setTitle("Market place")
                )
                ->addActionGroup(
                    (new SceneActionGroup())
                        ->setSorting(3)
                        ->setTitle("Tavern street")
                ),
            "forest" => (new Scene())
                ->setTitle("The forest")
                ->setDescription("The forest is home to evil monsters, critters, and people that prefer more 
                    discreet places than a lively village. The dense trees and the shades from the leaves restrict your vision
                    to a few meters at most. If not for your trained eyes, the paths would stay hidden. You move as silently
                    a mild breeze over the earth you are walking. You try to avoid to step on little branches or any of the
                    bleached, brittle bones that scatter the floor. You are trying to hide your presence from the things living here."),
            "weapons" => (new Scene())
                ->setTitle("MightyE's weapon shop")
                ->setTemplateClass(WeaponShopTemplate::class)
                ->setDescription("MightyE stands behind a table and doesn't seemingly care when you enter the shop. 
                    From experience, you know that he is aware of every single one of your movements. He might be a humble
                    merchant, but one who still has the aura of a man who used his wares to kill opponents way stronger than
                    yourself.
                    
                    The massive handle of a claymore is visible behind his back. Its shimmers in the light of the torches 
                    brighter than his bald head he keeps shaving for strategic advantages, in addition to nature insisting
                    on a certain level of baldness.
                    
                    Finally, MightyE nods at you, stroking his goatee and wishing for an excuse to use one of his weapons."),
            "armors" => (new Scene())
                ->setTitle("Pegasus armor")
                ->setDescription("The fair and beautiful Pegasus greets you with a warm smile as you stroll over 
                    to her brightly colored wagon, which is placed, not out of coincidence, right next to MightyE's weapon
                    shop. Her outfit is as brightly coloured and outrageous as her wagon, and it is almost (but not quite)
                    enough to make you look away from her huge grey eyes and flashes of her skin between her 
                    not-quite-sufficient clothes.")
        ];

        $villageToForestConnection = $scenes["village"]->connectTo($scenes["forest"], sourceLabel: "The forest", targetLabel: "Back to the village");
        $scenes["village"]->getActionGroups()->get(0)->addConnection($villageToForestConnection);

        $villageToWeaponsConnection = $scenes["village"]->connectTo($scenes["weapons"], sourceLabel: "MightyE's weapons", targetLabel: "Back to the village");
        $scenes["village"]->getActionGroups()->get(2)->addConnection($villageToWeaponsConnection);

        $villageToArmorsConnection = $scenes["village"]->connectTo($scenes["armors"], sourceLabel: "Pegasus armors", targetLabel: "Back to the village");
        $scenes["village"]->getActionGroups()->get(2)->addConnection($villageToArmorsConnection);

        foreach ($scenes as $scene) {
            $manager->persist($scene);
        }

        $manager->flush();
    }
}