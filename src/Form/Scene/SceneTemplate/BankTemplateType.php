<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\BankTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type BankTemplateConfiguration from BankTemplate
 * @implements TypeProvidesDefaultDataInterface<BankTemplateConfiguration>
 * @extends AbstractType<BankTemplateConfiguration>
 */
class BankTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function getDefaultData(): array
    {
        return [
            "tellerName" => "Elessa",
            "accountName" => "default",
            "text" => [
                "deposit" => <<<TXT
                    {{ tellerName }} records your deposit of {{ amount }} gold in her ledger.
                    {% if goldinbank < 0 %} 
                         <<Thank you, {{ character.name }}. You now have a debt of {{ goldInBank|abs }} gold to the bank and {{ goldInHand }} gold in hand.>>
                    {% else %}
                         <<Thank you, {{ character.name }}. You now have a balance of {{ goldInBank }} gold in the bank and {{ goldInHand }} gold in hand.>>
                    {% endif %}
                    TXT,
                "withdraw" => <<<TXT
                    {{ tellerName }} records your withdrawal of {{ amount }} gold in her ledger.
                    <<Thank you, {{ character.name }}. You now have a balance of {{ goldInBank }} gold in the bank and {{ goldInHand }} gold in hand.>>
                    TXT,
            ],
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add("tellerName", TextType::class, [
                "help" => "The name of the teller. Can be referenced in the texts to prevent to spell out the name multiple times",
                "required" => false,
                "constraints" => [
                    new NotBlank(),
                ],
                "data" => $defaultData["tellerName"],
            ])
            ->add("accountName", TextType::class, [
                "help" => "The account identifier. Choosing a different account names across scenes enables independent bank accounts.",
                "required" => false,
                "constraints" => [
                    new NotBlank(),
                ],
                "data" => $defaultData["accountName"],
            ])
            ->add(
                $builder->create("text", GroupedFormType::class, options: [
                    "inherit_data" => false,
                    "help" => "Common fields are: tellerName, amount, goldInBank, goldInHand, and character.name",
                    "constraints" => [
                        new Valid(),
                    ]
                ])
                ->add("deposit", TextareaType::class, [
                    "help" => "Text that is rendered if money is deposited into the bank account.",
                    "required" => false,
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["deposit"],
                ])
                ->add("withdraw", TextareaType::class, [
                    "help" => "Text that is rendered if money is withdrawed from the bank account.",
                    "required" => false,
                    "constraints" => [
                        new NotBlank(),
                    ],
                    "data" => $defaultData["text"]["withdraw"],
                ])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
    }

    public function getParent(): string
    {
        return GroupedFormType::class;
    }
}