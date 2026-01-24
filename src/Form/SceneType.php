<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Entity\Mapped\Scene;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @extends AbstractType<Scene>
 */
class SceneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("title")
            ->add("description")
            ->add("actionGroups", LiveCollectionType::class, [
                "entry_type" => SceneActionGroupType::class,
                "constraints" => [
                    new Assert\Valid(),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault("data_class", Scene::class);
    }
}