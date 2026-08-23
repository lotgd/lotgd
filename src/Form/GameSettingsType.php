<?php
declare(strict_types=1);

namespace LotGD2\Form;

use LotGD2\Event\FormExtensionEvent;
use LotGD2\Twig\Component\Admin\GameSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-import-type GameSettingsDataType from GameSettings
 * @extends AbstractType<GameSettingsDataType>
 */
class GameSettingsType extends AbstractType {
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $formEvent = new FormExtensionEvent($builder, $options);
        $this->eventDispatcher->dispatch($formEvent, GameSettings::FormExtensionEventName);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

    }
}