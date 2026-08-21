<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Game\Character\CharacterService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @extends AbstractType<Character>
 */
class CharacterType extends AbstractType
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("name", TextType::class, [
                "help" => "The name of your character. Cannot be changed after creation.",
                "disabled" => !$options["new"],
            ])
        ;

        $formEvent = new FormExtensionEvent($builder, $options);
        $this->eventDispatcher->dispatch($formEvent, CharacterService::CharacterFormExtensionEventName);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("data_class", Character::class);

        $resolver->define("new")
            ->default(true)
            ->allowedTypes("boolean")
            ->required();
    }
}
