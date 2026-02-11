<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type FightTemplateConfiguration from FightTemplate
 * @extends AbstractType<FightTemplateConfiguration>
 * @implements TypeProvidesDefaultDataInterface<FightTemplateConfiguration>
 */
class FightTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("searchFightAction", TextType::class, options: [
                "required" => true,
                "label" => "Action Name to search for a normal fight",
                "data" => $defaultData["searchFightAction"],
            ])
            ->add("searchSlummingAction", TextType::class, options: [
                "required" => true,
                "label" => "Action Name to search for an easy fight",
                "data" => $defaultData["searchSlummingAction"],
            ])
            ->add("searchThrillseekingAction", TextType::class, options: [
                "required" => true,
                "label" => "Action Name to search for a difficult fight",
                "data" => $defaultData["searchThrillseekingAction"],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", "Provides the possibility to search for fights.");
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }

    public function getDefaultData(): array
    {
        return [
            "searchFightAction" => "Search for a fight",
            "searchSlummingAction" => "Go Slumming",
            "searchThrillseekingAction" => "Go Thrillseeking",
        ];
    }
}