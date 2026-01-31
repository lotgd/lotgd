<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Form\GroupedFormType;
use LotGD2\Game\Enum\SceneConnectionType as SceneConnectionTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * @extends AbstractType<SceneConnection>
 */
class SceneConnectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("type", EnumType::class, [
                "class" => SceneConnectionTypeEnum::class,
            ])
            ->add(
                $builder->create("source", GroupedFormType::class, [
                    "help" => "All elements here are used if the connection gets rendered on the source side of the connection"
                ])
                ->add("sourceScene", EntityType::class, options: [
                    "disabled" => true,
                    "class" => Scene::class,
                    "choice_label" => "title",
                ])
                ->add("sourceActionGroup", EntityType::class, options: [
                    "class" => SceneActionGroup::class,
                    "choice_label" => "title",
                    "choices" => $options["source_action_groups"],
                    "required" => false,
                ])
                ->add("sourceLabel", options: [
                    "help" => "This is the label that is shown when a action is created towards the target",
                    "required" => false,
                ])
                ->add("sourceExpression", options: [
                    "required" => false,
                ])
            )
            ->add(
                $builder->create("target", GroupedFormType::class, [
                    "help" => "All elements here are used if the connection gets rendered on target source side of the connection"
                ])
                ->add("targetScene", EntityType::class, options: [
                    "disabled" => true,
                    "class" => Scene::class,
                    "choice_label" => "title",
                ])
                ->add("targetActionGroup", EntityType::class, options: [
                    "class" => SceneActionGroup::class,
                    "choice_label" => "title",
                    "choices" => $options["target_action_groups"],
                    "required" => false,
                ])
                ->add("targetLabel", options: [
                    "help" => "This is the label that is shown when a action is created towards the source",
                    "required" => false,
                ])
                ->add("targetExpression", options: [
                    "required" => false,
                ])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault("data_class", SceneConnection::class);
        $resolver->define("source_action_groups")->allowedTypes(SceneActionGroup::class . "[]", "array", Collection::class)->default([]);
        $resolver->define("target_action_groups")->allowedTypes(SceneActionGroup::class . "[]", "array", Collection::class)->default([]);
    }
}