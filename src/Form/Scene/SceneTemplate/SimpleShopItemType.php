<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Game\Scene\SceneTemplate\SimpleShopTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @phpstan-import-type SimpleShopItem from SimpleShopTemplate
 * @extends AbstractType<SimpleShopItem>
 */
class SimpleShopItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("name", TextType::class, options: [
                "required" => true,
                "constraints" => [
                    new NotBlank(),
                ]
            ])
            ->add("strength", IntegerType::class, options: [
                "required" => true,
                "constraints" => [
                    new GreaterThanOrEqual(0),
                ]
            ])
            ->add("price", IntegerType::class, options: [
                "required" => true,
                "constraints" => [
                    new GreaterThanOrEqual(0),
                ]
            ])
        ;
    }
}