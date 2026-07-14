<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Entity\Chance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault("data_class", Chance::class);
    }
}