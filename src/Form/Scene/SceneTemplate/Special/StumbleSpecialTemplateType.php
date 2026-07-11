<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate\Special;

use LotGD2\Form\CharacterExpressionType;
use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\Special\StumbleSpecialTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @phpstan-import-type StumbleSpecialConfiguration from StumbleSpecialTemplate
 * @implements TypeProvidesDefaultDataInterface<StumbleSpecialConfiguration>
 * @extends AbstractType<StumbleSpecialConfiguration>
 */
class StumbleSpecialTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", <<<TXT
            This template creates a special event in which random one-off events can happen to the player.
            TXT
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("damageChance", NumberType::class, [
                "help" => "Probability that the player takes damage from the event. Context will have the value 
                    tookDamage available to adjust the text accordingly. Its 0 when no damage occured, and equals
                    to the amount of damage the character took.
                    ",
                "required" => false,
                "constraints" => [
                    new NotBlank(),
                    new Range(min: 0, max: 100),
                ],
                "data" => $defaultData["damageChance"],
            ])
            ->add("minDamage", CharacterExpressionType::class, [
                "help" => "Minimum amount of damage. Can be an expression.",
                "required" => false,
                "data" => $defaultData["minDamage"],
            ])
            ->add("maxDamage", CharacterExpressionType::class, [
                "help" => "Maximum amount of damage. Can be an expression.",
                "required" => false,
                "data" => $defaultData["maxDamage"],
            ])
            ->add("playerCanDie", CheckboxType::class, [
                "help" => "If this is turned off, characters can get out of the event with 1 healthpoint left.",
                "required" => false,
                "data" => $defaultData["playerCanDie"],
            ])
        ;
    }

    public function getDefaultData(): array
    {
        return [
            "damageChance" => 100,
            "minDamage" => "character.level",
            "maxDamage" => "character.level*3",
            "playerCanDie" => true,
        ];
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }
}