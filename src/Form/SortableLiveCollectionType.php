<?php
declare(strict_types=1);

namespace LotGD2\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * Based on LiveCollectionType
 */
final class SortableLiveCollectionType extends AbstractType
{
    public function getParent(): string
    {
        return LiveCollectionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['allow_sort']) {
            $prototype = $builder->create('push_up', $options['button_push_up_type'], $options['button_push_up_options']);
            $builder->setAttribute('button_push_up_prototype', $prototype->getForm());

            $prototype = $builder->create('push_down', $options['button_push_down_type'], $options['button_push_down_options']);
            $builder->setAttribute('button_push_down_prototype', $prototype->getForm());
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {

    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $prefixOffset = -2;
        // check if the entry type also defines a block prefix
        /** @var FormInterface $entry */
        foreach ($form as $entry) {
            if ($entry->getConfig()->getOption('block_prefix')) {
                --$prefixOffset;
            }

            break;
        }

        foreach ($view as $entryView) {
            array_splice($entryView->vars['block_prefixes'], $prefixOffset, 0, 'sortable_live_collection_entry');
        }

        if ($form->getConfig()->hasAttribute('button_push_up_prototype')) {
            $prototype = $form->getConfig()->getAttribute('button_push_up_prototype');

            $prototypes = [];
            foreach ($form as $k => $entry) {
                $prototypes[$k] = clone $prototype;
                $prototypes[$k]->setParent($entry);
            }

            foreach ($view as $k => $entryView) {
                $entryView->vars['button_push_up'] = $prototypes[$k]->createView($entryView);

                $attr = $entryView->vars['button_push_up']->vars['attr'];
                $attr['data-action'] ??= 'live#action';
                $attr['data-live-action-param'] ??= 'pushUpCollectionItem';
                $attr['data-live-name-param'] ??= $view->vars['full_name'];
                $attr['data-live-index-param'] ??= $k;
                $attr['data-live-sort-field-name-param'] ??= $form->getConfig()->getOption("sort_field_name");
                $entryView->vars['button_push_up']->vars['attr'] = $attr;

                array_splice($entryView->vars['button_push_up']->vars['block_prefixes'], 1, 0, 'live_collection_button_push_up');
            }
        }

        if ($form->getConfig()->hasAttribute('button_push_down_prototype')) {
            $prototype = $form->getConfig()->getAttribute('button_push_down_prototype');

            $prototypes = [];
            foreach ($form as $k => $entry) {
                $prototypes[$k] = clone $prototype;
                $prototypes[$k]->setParent($entry);
            }

            foreach ($view as $k => $entryView) {
                $entryView->vars['button_push_down'] = $prototypes[$k]->createView($entryView);

                $attr = $entryView->vars['button_push_down']->vars['attr'];
                $attr['data-action'] ??= 'live#action';
                $attr['data-live-action-param'] ??= 'pushUpCollectionItem';
                $attr['data-live-name-param'] ??= $view->vars['full_name'];
                $attr['data-live-index-param'] ??= $k;
                $attr['data-live-sort-field-name-param'] ??= $form->getConfig()->getOption("sort_field_name");
                $entryView->vars['button_push_down']->vars['attr'] = $attr;

                array_splice($entryView->vars['button_push_down']->vars['block_prefixes'], 1, 0, 'live_collection_button_push_down');
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'button_push_up_type' => ButtonType::class,
            'button_push_down_type' => ButtonType::class,
            'button_push_up_options' => [],
            'button_push_down_options' => [],
            'sort_field_name' => null,
            'allow_sort' => true,
            'by_reference' => false,
        ]);

        $resolver->setAllowedTypes("sort_field_name", ["string", "null"]);
    }
}