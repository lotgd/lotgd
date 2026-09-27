<?php
declare(strict_types=1);

namespace LotGD2\Form\DataObject;

use LotGD2\Entity\DataObject\InnFlirtOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<InnFlirtOption>
 */
class InnFlirtOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("charmRequirement", IntegerType::class, [
                "label" => "Charm Requirement",
            ])
            ->add("name", TextType::class, [
                "label" => "Name of the flirt action",
                "help" => "The name is used to label the action in the UI.",
            ])
            ->add("successMessage", TextareaType::class, [
                "label" => "Success message",
                "help" => "The message displayed to the player when the flirt action succeeds.",
            ])
            ->add("addCharmIfSuccessful", CheckboxType::class, [
                "label" => "Add charm point if successful",
                "help" => "If checked, the player will receive a charm point if the flirt action succeeds.",
                "required" => false,
            ])
            ->add("charmRewardRange", ValueRangeType::class, [
                "label" => "Value range for charm reward",
                "help" => "Charm is rewarded only if the flirt action succeeds and the value is within the specified range.",
            ])
            ->add("isExhaustiveIfSuccessful", CheckboxType::class, [
                "label" => "Is exhaustive if successful",
                "help" => "If checked, the flirt action will be exhaustive if it succeeds and removes 2 turns.",
                "required" => false,
            ])
            ->add("failureMessage", TextareaType::class, [
                "label" => "Failure message",
                "help" => "The message displayed to the player when the flirt action fails.",
            ])
            ->add("removeCharmIfFailure", CheckboxType::class, [
                "label" => "Remove charm point if failure",
                "help" => "If checked, the player will lose a charm point if the flirt action fails.",
                "required" => false,
            ])
            ->add("charmRemovalRange", ValueRangeType::class, [
                "label" => "Value range for charm removal",
                "help" => "Charm is removed only if the flirt action fails and the value is within the specified range.",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", InnFlirtOption::class);
    }
}
