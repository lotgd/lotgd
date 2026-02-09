<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\TrainingTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type TrainingTemplateConfiguration from TrainingTemplate
 * @implements TypeProvidesDefaultDataInterface<TrainingTemplateConfiguration>
 * @extends AbstractType<TrainingTemplateConfiguration>
 */
class TrainingTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("campLeader", TextType::class, options: [
                "required" => true,
                "help" => "The name of the camp leader. Can be referenced within texts as campLeader.",
                "data" => $defaultData["campLeader"],
                "constraints" => [
                    new NotBlank(),
                ]
            ])
            ->add(
                $builder->create("text", GroupedFormType::class, options: [
                    "inherit_data" => false,
                    "help" => "Common fields are: campLeader, master.name, requiredExperience, experience, weapon and armor.",
                    "constraints" => [
                        new Valid(),
                    ]
                ])
                ->add("maxLevelReached", TextareaType::class, [
                    "required" => true,
                    "help" => "Text that gets displayed with max level has been reached.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["maxLevelReached"],
                ])
                ->add("askExperience", TextareaType::class, [
                    "required" => true,
                    "help" => "Text that gets displayed if the character asks their master how much experience they still require.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["askExperience"],
                ])
                ->add("seenMaster", TextareaType::class, [
                    "required" => true,
                    "help" => "Text that is displayed if the master has already been seen that day.",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["seenMaster"],
                ])
                ->add("absoluteDefeat", TextareaType::class, [
                    "required" => true,
                    "help" => "Text that is displayed if the master is challenged without passing the experience requirement (experience < requiredExperience)",
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["absoluteDefeat"],
                ])
            )
        ;
    }

    public function getDefaultData(): array
    {
        return [
            "campLeader" => "Bluspring",
            "text" => [
                "maxLevelReached" => <<<TXT
                    You stroll into the battle grounds. Younger warriors huddle together and point as you pass by.
                    You know this place well. {{ campLeader }}  hails you, and you grasp their hand firmly. There 
                    is nothing left for you here but memories. You remain a moment longer, and look at the warriors 
                    in training before you turn to return to the village.
                    TXT,
                "askExperience" => <<<TXT
                    You approach {{ master.name }} timidly and inquire as to your standing in the class.
                    {% if experience >= requiredExperience %}
                        They say, <<Gee, your muscles are getting bigger than mine...>>
                    {% else %}
                        They state that you will need {{ requiredExperience - experience }} more experience 
                        before you are ready to challenge him in battle.
                    {% endif %}
                    TXT,
                "seenMaster" => <<<TXT
                    You think that, perhaps, you've seen enough of your master for today, the lessons you learned 
                    earlier prevent you from so willingly subjecting yourself to that sort of humiliation again.
                    TXT,
                "absoluteDefeat" => <<<TXT
                    You ready your {{ weapon }} and {{ armor }} and approach {{ master.name }}.
                    
                    A small crowd of onlookers has gathered, and you briefly notice the smiles on their faces, 
                    but you feel confident. You bow before them, and execute a perfect spin-attack, only to 
                    realize that you are holding NOTHING! {{ master.name }} stands before you holding your weapon.
                    Meekly you retrieve your {{ weapon }} , and slink out of the training grounds to the sound 
                    of boisterous guffaws.
                    TXT,
            ]
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }
}