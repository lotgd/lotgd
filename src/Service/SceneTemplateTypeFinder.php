<?php
declare(strict_types=1);

namespace LotGD2\Service;

use LotGD2\Attribute\TemplateType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Form\AbstractType;

class SceneTemplateTypeFinder
{
    /**
     * @param class-string<SceneTemplateInterface<array<string, mixed>>> $templateClass
     * @return null|class-string<AbstractType<mixed>&TypeProvidesDefaultDataInterface<array<mixed>>>
     */
    public function find(string $templateClass): ?string
    {
        if ($templateClass === "") {
            return null;
        }

        $reflection = new ReflectionClass($templateClass);
        $attributes = $reflection->getAttributes(TemplateType::class);

        if (count($attributes) === 0) {
            return null;
        }

        /** @var TemplateType $attribute */
        $attribute = $attributes[0]->newInstance();

        if (!is_subclass_of($attribute->type, AbstractType::class)) {
            return null;
        }

        return $attribute->type;
    }
}