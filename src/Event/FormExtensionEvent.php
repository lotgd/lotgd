<?php
declare(strict_types=1);

namespace LotGD2\Event;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

class FormExtensionEvent extends Event
{
    public function __construct(
        public readonly FormBuilderInterface $builder,
        public readonly array $options
    ) {

    }

    public function add(string $propertyName, string $type, array $options = []): self
    {
        $this->builder->add($this->propertyToFormName($propertyName), $type, $options);
        return $this;
    }

    public function propertyToFormName(string $property): string
    {
        return str_replace(".", "_", $property);
    }

    public function formNameToProperty(string $name): string
    {
        return str_replace("_", ".", $name);
    }
}