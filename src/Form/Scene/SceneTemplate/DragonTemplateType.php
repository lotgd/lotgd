<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type DragonTemplateConfiguration from DragonTemplate
 * @extends AbstractType<DragonTemplateConfiguration>
 * @implements TypeProvidesDefaultDataInterface<DragonTemplateConfiguration>
 */
class DragonTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("dragonName", TextType::class, options: [
                "required" => true,
                "data" => $defaultData["dragonName"],
            ])
            ->add("dragonWeapon", TextType::class, options: [
                "required" => true,
                "data" => $defaultData["dragonWeapon"],
            ])
            ->add($builder
                ->create("text", GroupedFormType::class, [
                    "required" => false,
                    "help" => "No common elements.",
                    "inherit_data" => false,
                    "constraints" => [
                        new Valid(),
                    ]
                ])
                ->add("fightIntro", TextareaType::class, [
                    "help" => "Displayed if the Green Dragon is sought out.",
                ])
                ->add("epilogue", TextareaType::class, [
                    "help" => "Displayed if the Green Dragon was slain.",
                ])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }

    public function getDefaultData(): array
    {
        return [
            "dragonName" => "The Green Dragon",
            "dragonWeapon" => "Great Flaming Maw",
            "text" => [
                "fightIntro" => <<<TXT
                    Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching 
                    the great Green Dragon sleeping, so that you might slay it with a minimum of pain. Sadly, this 
                    is not to be the case, for as you round a corner within the cave you discover the great beast 
                    sitting on its haunches on a huge pile of gold, picking its teeth with a rib.
                    TXT,
                "epilogue" => <<<TXT
                    Before you, the great dragon lies immobile, its heavy breathing like acid to your lungs. You are 
                    covered, head to toe, with the foul creature's thick black blood. The great beast begins to move 
                    its mouth.  You spring back, angry at yourself for having been fooled by its ploy of death, 
                    and watch for its huge tail to come sweeping your way. But it does not. Instead the dragon begins to speak.
                    
                    <<Why have you come here mortal? What have I done to you?>> it says with obvious effort. <<Always 
                    my kind are sought out to be destroyed. Why? Because of stories from distant lands that tell of 
                    dragons preying on the weak? I tell you that these stories come only from misunderstanding of us, 
                    and not because we devour your children.>> The beast pauses, breathing heavily before continuing, 
                    <<I will tell you a secret. Behind me now are my eggs. They will hatch, and the young will battle 
                    each other. Only one will survive, but she will be the strongest. She will quickly grow, and be 
                    as powerful as me.>> Breath comes shorter and shallower for the great beast.

                    <<Why do you tell me this? Don't you know that I will destroy your eggs?>> you ask. 

                    <<No, you will not, for I know of one more secret that you do not.>>

                    <<Pray tell oh mighty beast!>>

                    The great beast pauses, gathering the last of its energy. <<Your kind cannot tolerate the blood 
                    of my kind. Even if you survive, you will be a feeble creature, barely able to hold a weapon, 
                    your mind blank of all that you have learned. No, you are no threat to my children, for you are 
                    already dead!>>
                    
                    Realizing that already the edges of your vision are a little dim, you flee from the cave, bound 
                    to reach the healer's hut before it is too late. Somewhere along the way you lose your weapon, 
                    and finally you trip on a stone in a shallow stream, sight now limited to only a small circle 
                    that seems to float around your head. As you lay, staring up through the trees, you think that 
                    nearby you can hear the sounds of the village. Your final thought is that although you defeated 
                    the dragon, you reflect on the irony that it defeated you.

                    As your vision winks out, far away in the dragon's lair, an egg shuffles to its side, and a small 
                    crack appears in its thick leathery skin.

                    You wake up in the midst of some trees.  Nearby you hear the sounds of a village. Dimly you remember 
                    that you are a new warrior, and something of a dangerous Green Dragon that is plaguing the area. 
                    You decide you would like to earn a name for yourself by perhaps some day confronting this vile creature.
                    TXT,
            ]
        ];
    }
}