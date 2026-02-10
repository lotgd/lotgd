<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\HealerTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type HealerTemplateConfiguration from HealerTemplate
 * @implements TypeProvidesDefaultDataInterface<HealerTemplateConfiguration>
 * @extends AbstractType<HealerTemplateConfiguration>
 */
class HealerTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", "The healer template provides functionality of a healer: Filling up 
        hit points against gold. On level 1, the healing is usually to not have users stuck after dying repeatedly.");
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("stealHealth", CheckboxType::class, [
                "required" => false,
                "data" => $defaultData["stealHealth"],
            ])
            ->add("actionGroupPotionTitle", TextType::class, [
                "required" => false,
                "label" => "Action group label for potions",
                "data" => $defaultData["actionGroupPotionTitle"],
            ])
            ->add("actionCompleteHealingTitle", TextType::class, [
                "required" => false,
                "label" => "Action name for complete healing",
                "data" => $defaultData["actionCompleteHealingTitle"],
            ])
            ->add($builder
                ->create("text", GroupedFormType::class, [
                    "required" => false,
                    "help" => "Common element is price.",
                    "inherit_data" => true,
                    "constraints" => [
                        new Valid(),
                    ]
                ])
                ->add("onEntryAndHealthy", TextareaType::class, [
                    "required" => true,
                    "label" => "Text addition on entering the scene at full health",
                    "help" => "Text that gets added to the default scene description when the scene is entered and the character is at full health.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["onEntryAndHealthy"],
                ])
                ->add("onEntryAndDamaged", TextareaType::class, [
                    "required" => true,
                    "label" => "Text addition on entering the scene below full health",
                    "help" => "Text that gets added to the default scene description when the scene is entered and the character is damaged.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["onEntryAndDamaged"],
                ])
                ->add("onEntryAndOverhealed", TextareaType::class, [
                    "required" => true,
                    "label" => "Text addition on entering the scene above full health",
                    "help" => "Text that gets added to the default scene description when the scene is entered and the character is 'overhealed'. Will not get displayed if 'steal health' is off.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["onEntryAndOverhealed"],
                ])
                ->add("onHealEnoughGold", TextareaType::class, [
                    "required" => true,
                    "label" => "Text on successfully buying a potion",
                    "help" => "Text that gets displayed when a potion is bought with enough gold. Besides price, amount is also available.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["onHealEnoughGold"],
                ])
                ->add("onHealNotEnoughGold", TextareaType::class, [
                    "required" => true,
                    "label" => "Text on buying a potion without enough gold.",
                    "help" => "Text that gets displayed when a potion is bought without enough gold.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["onHealNotEnoughGold"],
                ])
            )
        ;
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }

    public function getDefaultData(): array
    {
        return [
            "stealHealth" => true,
            "actionGroupPotionTitle" => "Potions",
            "actionCompleteHealingTitle" => "Complete Healing",
            "text" => [
                "onEntryAndDamaged" => <<<TXT
                    <<See you, I do.  Before you did see me, I think, hmm?>> the old thing remarks. <<Know you, I do; healing you seek. 
                    Willing to heal am I, but only if willing to pay are you.>>

                    <<Uh, um.  How much?>> you ask, ready to be rid of the smelly old thing.

                    The old being thumps your ribs with a gnarly staff.  <<For you... {{ price }} gold pieces for a complete heal!!>> it says 
                    as it bends over and pulls a clay vial from behind a pile of skulls sitting in the corner. The view of the thing bending 
                    over to remove the vial almost does enough mental damage to require a larger potion. <<I also have some, erm... <.bargain.> 
                    potions available>> it says as it gestures at a pile of dusty, cracked vials. They'll heal a certain percent of your damage.
                    TXT,
                "onEntryAndOverhealed" => <<<TXT
                    The old creature glances at you, then in a whirlwind of movement that catches you completely off guard, brings its gnarled 
                    staff squarely in contact with the back of your head. You gasp as you collapse to the ground.
                    
                    Slowly you open your eyes and realize the beast is emptying the last drops of a clay vial down your throat.
                    
                    <<No charge for that potion.>> is all it has to say. You feel a strong urge to leave as quickly as you can.
                    TXT,
                "onEntryAndHealthy" => <<<TXT
                    The old creature grunts as it looks your way. <<Need a potion, you do not.  Wonder why you bother me, I do.>> says the hideous 
                    thing. The aroma of its breath makes you wish you hadn't come in here in the first place. You think you had best leave.
                    TXT,
                "onHealEnoughGold" => <<<TXT
                    {% if price > 0 %}
                        With a grimace, you up-end the potion the creature hands you, and despite the foul flavor, you feel a warmth spreading through 
                        your veins as your muscles knit back together. Staggering some, you hand it {{ price }} gold and are ready to be out of here.
                    {% else %}
                        With a grimace, you up-end the potion the creature hands you, and despite the foul flavor, you feel a warmth spreading through 
                        your veins. Staggering some you are ready to be out of here.
                    {% endif %}
                    
                    You have been healed for {{ amount }} points!
                    TXT,
                "onHealNotEnoughGold" => <<<TXT
                    The old creature pierces you with a gaze hard and cruel. Your lightning quick reflexes enable you to dodge the blow from its 
                    gnarled staff. Perhaps you should get some more money before you attempt to engage in local commerce.
                    
                    You recall that the creature had asked for {{ price }} gold.
                    TXT,
            ]
        ];
    }
}