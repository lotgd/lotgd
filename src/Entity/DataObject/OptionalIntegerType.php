<?php
declare(strict_types=1);

namespace LotGD2\Entity\DataObject;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<?int>
 */
class OptionalIntegerType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($value) {
                    return $value === null ? null : (int) $value;
                },
                function ($value) {
                    return $value === null ? null : (int) $value;
                }
            )
        );
    }
}