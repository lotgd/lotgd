<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Game\ExpressionService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\ExpressionSyntax;

/**
 * @extends AbstractType<mixed>
 */
class CharacterExpressionType extends AbstractType
{
    public function __construct(
        private readonly ExpressionService $expressionService,
    ) {

    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars["objectNamespace"] = $options['objectNamespace'];
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return "character_expression";
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "constraints" => [
                new ExpressionSyntax(allowedVariables: $this->expressionService->getNames()),
            ]
        ]);

        $resolver->define("objectNamespace")->allowedTypes("array")->default($this->expressionService->getNames(true));
    }
}