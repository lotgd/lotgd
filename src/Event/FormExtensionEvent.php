<?php
declare(strict_types=1);

namespace LotGD2\Event;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @template-covariant TData
 */
class FormExtensionEvent extends Event
{
    /**
     * @param FormBuilderInterface<covariant TData> $builder
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly FormBuilderInterface $builder,
        public readonly array $options
    ) {

    }

    /**
     * @param string $propertyName
     * @param string $type
     * @param array<string, mixed> $options
     * @return self<TData>
     */
    public function add(string $propertyName, string $type, array $options = []): self
    {
        $this->builder->add($propertyName, $type, $options);
        return $this;
    }
}