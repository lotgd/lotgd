<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\Handler\GenderHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Character>
 */
class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("name", TextType::class, [
                "help" => "The name of your character. Cannot be changed after creation.",
            ])
            ->add(
                $builder->create(GenderHandler::GenderProperty, FormType::class, [
                    'property_path' => 'properties['. GenderHandler::GenderProperty .']',
                    "by_reference" => false,
                    "label" => "Gender settings",
                    "help" => <<<TXT
                        These settings influence primarily texts that refer to the character, or events that happen
                        to your character.
                        TXT,
                ])
                ->add(GenderHandler::PronounsGenderProperty, ChoiceType::class, [
                    "choices" => [
                        "Male (he/him)" => "male",
                        "Female (she/her)" => "female",
                        "Other (they/them)" => "other",
                    ],
                    "help" => "This setting is used to refer to your character in third person. Can be changed afterwards.",
                ])
                ->add(GenderHandler::PartnerGenderProperty, ChoiceType::class, [
                    "choices" => [
                        "Male" => "male",
                        "Female" => "female",
                    ],
                    "help" => <<<TXT
                        This setting is used in texts to determine the preferred partner. In the classic inn,
                        this is the decision between Seth or Violet; in specials, it could be the difference between
                        a prince or a princess.
                        TXT,
                ])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", Character::class);
    }
}
