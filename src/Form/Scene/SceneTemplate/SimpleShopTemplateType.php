<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\SimpleShopTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @phpstan-import-type SimpleShopConfiguration from SimpleShopTemplate
 * @extends AbstractType<SimpleShopConfiguration>
 * @implements TypeProvidesDefaultDataInterface<SimpleShopConfiguration>
 */
class SimpleShopTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", "Provides a simple shop for either armors or weapons.");
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("type", ChoiceType::class, [
                "choices" => [
                    "Weapons" => "weapon",
                    "Armors" => "armor",
                ],
                "constraints" => [
                    new NotBlank(),
                ]
            ])
            ->add("items", LiveCollectionType::class, [
                "entry_type" => SimpleShopItemType::class,
            ])
            ->add($builder
                ->create("text", GroupedFormType::class, [
                    "required" => false,
                    "help" => "Common variables are: item (current item name held, either armor or weapon), newitem (if a new item was selected), amount (trade in value).",
                    "inherit_data" => false,
                    "constraints" => [
                        new Valid(),
                    ]
                ])
                ->add("peruse", TextareaType::class, [
                    "label" => "Text for perusing",
                    "help" => "Displayed if the character views through the items of the shop. newitem is not set.",
                ])
                ->add("itemNotFound", TextareaType::class, [
                    "label" => "Error text for a disappeared item",
                    "help" => "Gets displayed if the user buys an item and this item ID cannot be found. This could happen if the 
                    scene settings get changed while a character peruses through the selection.",
                ])
                ->add("buy", TextareaType::class, [
                    "label" => "Text if item is bought",
                    "help" => "Gets displayed if the character buys an item and has enough gold. The variable newitem is set in this case.",
                ])
                ->add("notEnoughGold", TextareaType::class, [
                    "label" => "Text if item cannot bought due to not enough gold",
                    "help" => "Displayed if the character buys an item but has not enough gold available. The variable newitem is set in this case.",
                ])
            )
        ;
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }

    public function getDefaultData(): array
    {
        return [
            "type" => "weapon",
            "items" => [
                ["name" => "Round stone", "strength" => 1, "price" => 49],
            ],
            "text" => [
                "peruse" => <<<TXT
                    You stroll up to the counter and try your best to look like you know what most 
                    of these contraptions do. MightyE looks at you and says, <<I'll give you {{ amount }} trade-in 
                    value for your {{ item }}. Just click on the weapon you wish to buy, what ever <.click.> 
                    means>>, and looks utterly confused. He stands there for a few seconds, snapping his 
                    fingers and wondering if that is what is meant by <.click.>, before returning to his 
                    work: stand there and looking good.
                    TXT,
                "itemNotFound" => <<<TXT
                    MightyE looks at you, confused for a second, then realizes that 
                    you've apparently taken one too many bonks on the head, and nods and smiles.
                    TXT,
                "buy" => <<<TXT
                    MightyE takes your {{ item }} and promptly puts a price on it, setting it out for 
                    display with the rest of his weapons.
                    
                    In return, he hands you a shiny new {{ newitem }} which you swoosh around the room, nearly 
                    taking off MightyE's head, which he deftly ducks; you're not the first person to exuberantly 
                    try out a new weapon.
                    TXT,
                "notEnoughGold" => <<<TXT
                    Waiting until MightyE looks away, you reach carefully for the {{ newitem }}, which you silently 
                    remove from the rack upon which it sits. Secure in your theft, you turn around and head for the 
                    door, swiftly, quietly, like a ninja, only to discover that upon reaching the door, the ominous 
                    MightyE stands, blocking your exit. You execute a flying kick. Mid-flight, you hear the <<SHING>> 
                    of a sword leaving its sheath… your foot is gone. You land on your stump, and MightyE stands 
                    in the doorway, claymore once again in its back holster, with no sign that it had been used, 
                    his arms folded menacingly across his burly chest. <<Perhaps you'd like to pay for that?>> 
                    is all he has to say as you collapse at his feet, lifeblood staining the planks under your 
                    remaining foot.
                    
                    You wake up some time later, having been tossed unconscious into the street.
                    TXT,
            ],
        ];
    }
}