<?php
declare(strict_types=1);

namespace LotGD2\Form\DataObject;

use LotGD2\Entity\DataObject\Chance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @extends AbstractType<Chance>
 */
class ChanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new CallbackTransformer(
                function (Chance|int|float $chance): Chance {
                    if ($chance instanceof Chance) {
                        return $chance;
                    }

                    $numerator = (int)$chance;
                    $denominator = 100;

                    return new Chance($numerator, $denominator);
                },
                function (Chance|int|float $chance): Chance {
                    if ($chance instanceof Chance) {
                        return $chance;
                    }

                    $numerator = (int)$chance;
                    $denominator = 100;

                    return new Chance($numerator, $denominator);
                }
            ))
        ;

        $builder
            ->add("numerator", NumberType::class, [
                "constraints" => [
                    new Range(min: 0),
                ]
            ])
            ->add("denominator", NumberType::class, [
                "constraints" => [
                    new Range(min: 1),
                ]
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return "chance";
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", Chance::class);
        $resolver->setDefault("help", "Chance of something happening. Chance is given as x in y,
        for which x is the nominator and y the denominator. A chance of 1 in 5 is equal to a chance of 20%, for example.");
    }
}