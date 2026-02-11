<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Scene\SceneTemplate\BankTemplate;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Game\Scene\SceneTemplate\HealerTemplate;
use LotGD2\Game\Scene\SceneTemplate\SimpleShopTemplate;
use LotGD2\Game\Scene\SceneTemplate\TrainingTemplate;
use LotGD2\Service\SceneTemplateTypeFinder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @extends AbstractType<Scene>
 */
class SceneType extends AbstractType
{
    public function __construct(
        private readonly SceneTemplateTypeFinder $templateTypeFinder,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
            ->add("templateClass", ChoiceType::class, [
                "choices" => [
                    "Bank" => BankTemplate::class,
                    "Training Camp" => TrainingTemplate::class,
                    "Healer's Hut" => HealerTemplate::class,
                    "Dragon's Cave" => DragonTemplate::class,
                    "Search for Fights" => FightTemplate::class,
                    "Simple Shop" => SimpleShopTemplate::class,
                ],
                "required" => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", Scene::class);
    }

    /**
     * @param FormEvent $event
     * @return void
     */
    public function onPreSetData(FormEvent $event): void
    {
        /** @var Scene $scene */
        $scene = $event->getData();
        $form = $event->getForm();

        if (empty($scene)) {
            return;
        }

        if ($scene->templateClass !== null) {
            $formType = $this->templateTypeFinder->find($scene->templateClass);

            if ($formType) {
                $form->add("templateConfig", $formType);
            }
        } else {
            $scene->templateConfig = [];
            $event->setData($scene);
        }
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $scene = $event->getData();
        $form = $event->getForm();

        if (empty($scene)) {
            return;
        }

        if (!empty($scene["templateClass"])) {
            $formType = $this->templateTypeFinder->find($scene["templateClass"]);

            if ($formType) {
                $form->add("templateConfig", $formType);
            } else {
                return;
            }

            if (empty($scene["templateConfig"])) {
                $scene["templateConfig"] = new $formType()->getDefaultData();
                $event->setData($scene);
            }
        } else {
            $form->remove("templateConfig");
            $scene["templateConfig"] = [];
            $event->setData($scene);
        }
    }
}