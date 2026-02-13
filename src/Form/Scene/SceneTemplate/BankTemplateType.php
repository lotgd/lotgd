<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\BankTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @phpstan-import-type BankTemplateConfiguration from BankTemplate
 * @implements TypeProvidesDefaultDataInterface<BankTemplateConfiguration>
 * @extends AbstractType<BankTemplateConfiguration>
 */
class BankTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", "The bank template gives a player the opportunity to store money
        on a bank account. As all gold is lost after dying, the bank offers a possibility to savely store it away.
        Depending on the settings of the template, each bank gives access to the same, or to a different bank account.");
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
                "help" => "The account identifier. Choosing a different account names across scenes enables independent bank accounts.
                    Be careful with bank-account specific settings (such as the interest rate). If multiple apply, only the one
                    from the Scene with the lower ID will be used.",
                "required" => false,
                "constraints" => [
                    new NotBlank(),
                ],
                "data" => $defaultData["accountName"],
            ])
            ->add("minInterest", IntegerType::class, [
                "label" => "Minimum interest rate in %",
                "required" => true,
                "constraints" => [
                    new Range(min: 0),
                ],
                "data" => $defaultData["minInterest"],
            ])
            ->add("maxInterest", IntegerType::class, [
                "label" => "Maximum interest rate in %",
                "required" => true,
                "constraints" => [
                    new Range(min: 0),
                ],
                "data" => $defaultData["maxInterest"],
            ])
            ->add("maxGoldInBank", IntegerType::class, [
                "label" => "Maximum gold in bank before no interest is paid out.",
                "help" => "Set to 0 to disable. Prevents that rich people get too rich",
                "required" => true,
                "constraints" => [
                    new Range(min: 0),
                ],
                "data" => $defaultData["maxGoldInBank"],
            ])
            ->add("turnsLeftBeforeInterest", IntegerType::class, [
                "label" => "Maximum amount of turns before interest is paid out.",
                "help" => "Maximum amount of turns that a character can have left from the old day before interest is paid out. At 0, character must have exhausted all of their turns. -1 disables this feature.",
                "required" => true,
                "constraints" => [
                    new Range(min: -1),
                ],
                "data" => $defaultData["maxGoldInBank"],
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

    public function getParent(): string
    {
        return GroupedFormType::class;
    }

    public function getDefaultData(): array
    {
        return [
            "tellerName" => "Elessa",
            "accountName" => "default",
            "minInterest" => 1,
            "maxInterest" => 10,
            "maxGoldInBank" => 10000,
            "turnsLeftBeforeInterest" => 4,
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

}