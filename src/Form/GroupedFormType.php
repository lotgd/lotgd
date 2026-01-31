<?php
declare(strict_types=1);

namespace LotGD2\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupedFormType extends AbstractType
{
    public function getParent(): string
    {
        return FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", true);
    }
}