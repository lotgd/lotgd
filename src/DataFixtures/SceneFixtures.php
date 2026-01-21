<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Param\ParamBag;
use LotGD2\Entity\Scene;
use LotGD2\Entity\SceneActionGroup;
use LotGD2\Game\Scene\SceneTemplate\SimpleShopTemplate;

class SceneFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var Scene[] $scenes */
        $scenes = [
            "village" => new Scene()
                ->setDefaultScene(true)
                ->setTitle("Village square")
                ->setDescription("People are busy going on errands. No one really sees you. There are different 
                    shops around the square. The village is surrounded by a deep, black forest.")
                ->addActionGroup(
                    new SceneActionGroup()
                        ->setSorting(0)
                        ->setTitle("Outside")
                )
                ->addActionGroup(
                    new SceneActionGroup()
                        ->setSorting(1)
                        ->setTitle("Blade alley")
                )
                ->addActionGroup(
                    new SceneActionGroup()
                        ->setSorting(2)
                        ->setTitle("Market place")
                )
                ->addActionGroup(
                    new SceneActionGroup()
                        ->setSorting(3)
                        ->setTitle("Tavern street")
                ),
            "forest" => new Scene()
                ->setTitle("The forest")
                ->setDescription("The forest is home to evil monsters, critters, and people that prefer more 
                    discreet places than a lively village. The dense trees and the shades from the leaves restrict your vision
                    to a few meters at most. If not for your trained eyes, the paths would stay hidden. You move as silently
                    a mild breeze over the earth you are walking. You try to avoid to step on little branches or any of the
                    bleached, brittle bones that scatter the floor. You are trying to hide your presence from the things living here."),
            "weapons" => new Scene()
                ->setTitle("MightyE's weapon shop")
                ->setDescription("MightyE stands behind a table and doesn't seemingly care when you enter the shop. 
                    From experience, you know that he is aware of every single one of your movements. He might be a humble
                    merchant, but one who still has the aura of a man who used his wares to kill opponents way stronger than
                    yourself.
                    
                    The massive handle of a claymore is visible behind his back. Its shimmers in the light of the torches 
                    brighter than his bald head he keeps shaving for strategic advantages, in addition to nature insisting
                    on a certain level of baldness.
                    
                    Finally, MightyE nods at you, stroking his goatee and wishing for an excuse to use one of his weapons.")
                ->setTemplateClass(SimpleShopTemplate::class)
                ->setTemplateConfig(
                    SimpleShopTemplate::validateConfiguration([
                        "type" => "weapon",
                        "items" => [
                            ["name" => "Round stone", "strength" => 1, "price" => 49],
                            ["name" => "Pointy stone", "strength" => 2, "price" => 225],
                            ["name" => "Stone on a stick", "strength" => 3, "price" => 585],
                            ["name" => "Trowel", "strength" => 4, "price" => 990],
                            ["name" => "Short axe", "strength" => 5, "price" => 1575],
                            ["name" => "Garden hook", "strength" => 6, "price" => 2250],
                            ["name" => "Torch", "strength" => 7, "price" => 2790],
                            ["name" => "Pitchfork", "strength" => 8, "price" => 3420],
                            ["name" => "Shovel", "strength" => 9, "price" => 4230],
                            ["name" => "Hedge trimmer", "strength" => 10, "price" => 5040],
                            ["name" => "Axe", "strength" => 11, "price" => 5850],
                            ["name" => "Woodcarving Knife", "strength" => 12, "price" => 6840],
                            ["name" => "Cheap woodcutter axe", "strength" => 13, "price" => 8010],
                            ["name" => "Sharp woodcutter axe", "strength" => 14, "price" => 9000],
                            ["name" => "Large woodcutter axe", "strength" => 15, "price" => 10350],
                        ],
                        "text" => [
                            "peruse" => "You stroll up to the counter and try your best to look like you know what most 
                                of these contraptions do. MightyE looks at you and says, <<I'll give you {amount} trade-in 
                                value for your {weapon}. Just click on the weapon you wish to buy, what ever <.click.> 
                                means>>, and looks utterly confused. He stands there for a few seconds, snapping his 
                                fingers and wondering if that is what is meant by <.click.>, before returning to his 
                                work: stand there and looking good.",
                            "itemNotFound" => "MightyE looks at you, confused for a second, then realizes that 
                                you've apparently taken one too many bonks on the head, and nods and smiles.",
                            "buy" => "MightyE takes your {weapon} and promptly puts a price on it, setting it out for 
                                display with the rest of his weapons.
                                
                                In return, he hands you a shiny new {newweapon} which you swoosh around the room, nearly 
                                taking off MightyE's head, which he deftly ducks; you're not the first person to exuberantly 
                                try out a new weapon."
                        ],
                    ])
                ),
            "armors" => new Scene()
                ->setTitle("Pegasus armor")
                ->setDescription("The fair and beautiful Pegasus greets you with a warm smile as you stroll over 
                    to her brightly colored wagon, which is placed, not out of coincidence, right next to MightyE's weapon
                    shop.
                    
                    Her outfit is as brightly coloured and outrageous as her wagon, and it is almost (but not quite)
                    enough to make you look away from her huge grey eyes and flashes of her skin between her 
                    not-quite-sufficient clothes.")
                ->setTemplateClass(SimpleShopTemplate::class)
                ->setTemplateConfig(
                    SimpleShopTemplate::validateConfiguration([
                        "type" => "armor",
                        "items" => [
                            ["name" => "Fuzzy Slippers", "strength" => 1, "price" => 49],
                            ["name" => "Flannel Pajamas", "strength" => 2, "price" => 225],
                            ["name" => "Homespun Longjohns", "strength" => 3, "price" => 585],
                            ["name" => "Homespun Undershirt", "strength" => 4, "price" => 990],
                            ["name" => "Knitted Socks", "strength" => 5, "price" => 1575],
                            ["name" => "Knitted Gloves", "strength" => 6, "price" => 2250],
                            ["name" => "Old Leather Boots", "strength" => 7, "price" => 2790],
                            ["name" => "Homespun Pants", "strength" => 8, "price" => 3420],
                            ["name" => "Homespun Tunic", "strength" => 9, "price" => 4230],
                            ["name" => "Gypsy Cape", "strength" => 10, "price" => 5040],
                            ["name" => "Old Leather Cap", "strength" => 11, "price" => 5850],
                            ["name" => "Old Leather Bracers", "strength" => 12, "price" => 6840],
                            ["name" => "Traveller\'s Shield", "strength" => 13, "price" => 8010],
                            ["name" => "Old Leather Pants", "strength" => 14, "price" => 9000],
                            ["name" => "Old Leather Tunic", "strength" => 15, "price" => 10350],
                        ],
                        "text" => [
                            "peruse" => "You look over the various pieces of apparel, and wonder if Pegasus would be so 
                                good as to try some of them on for you, when you realize that she is busy staring dreamily 
                                at MightyE through the window of his shop as he, bare-chested, demonstrates the use of one 
                                of his fine wares to a customer.
                                
                                Noticing for a moment that you are browsing her wares, she glances at your {item} and says 
                                that she'll give you {amount} for them.",
                            "itemNotFound" => "Pegasus looks at you, confused for a second, then realizes that 
                                you've apparently taken one too many bonks on the head, and nods and smiles.",
                            "buy" => "Pegasus takes your gold, and much to your surprise she also takes your {item} and 
                                promptly puts a price on it, setting it neatly on another stack of clothes.
                                
                                In return, she hands you a beautiful new {newitem].
                                
                                You begin to protest, <<Won't I look silly wearing nothing but my {newitem}>> you ask. You 
                                ponder it a moment, and then realize that everyone else in the town is doing the same thing. 
                                <<Oh well, when in Rome...>>",
                        ],
                    ])
                )
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