<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Entity\Mapped\SceneActionGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SceneActionGroup>
 */
class SceneActionGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("id", options: [
                "disabled" => true
            ])
            ->add("title")
            ->add("sorting")
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault("data_class", SceneActionGroup::class);
    }
}