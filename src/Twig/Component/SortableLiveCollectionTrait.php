<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

trait SortableLiveCollectionTrait
{
    use LiveCollectionTrait;

    #[LiveAction]
    public function pushUpCollectionItem(
        PropertyAccessorInterface $propertyAccessor,
        #[LiveArg] string $name,
        #[LiveArg] int $index,
        #[LiveArg] ?string $sortFieldName,
    ): void {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        $keys = array_keys($data);
        $values = array_values($data);

        $sortIndex = array_search($index, $keys);

        dump($data);

        // Do nothing if top was reached
        if ($sortIndex === 0) {
            return;
        }

        $newSortIndex = $sortIndex - 1;

        $thisKey = $keys[$sortIndex];
        $thisValue = $values[$sortIndex];
        $aboveKey = $keys[$newSortIndex];
        $aboveValue = $values[$newSortIndex];

        $keys[$newSortIndex] = $thisKey;
        $keys[$sortIndex] = $aboveKey;

        $values[$newSortIndex] = $thisValue;
        $values[$sortIndex] = $aboveValue;

        $data = array_combine($keys, $values);

        dump($data);

        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);
    }
}