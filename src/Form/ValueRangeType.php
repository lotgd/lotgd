<?php
declare(strict_types=1);

namespace LotGD2\Form\DataObject;

use LotGD2\Entity\DataObject\OptionalIntegerType;
use LotGD2\Entity\DataObject\ValueRange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ValueRange>
 */
class ValueRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("minimum", OptionalIntegerType::class, [
                "required" => false,
            ])
            ->add("maximum", OptionalIntegerType::class, [
                "required" => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return "valueRange";
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", ValueRange::class);
        $resolver->setDefault("help", "Range of a value, min and max inclusive.");
    }
}