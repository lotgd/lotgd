<?php
declare(strict_types=1);

namespace LotGD2\Entity\Param;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, Param>
 * @implements IteratorAggregate<string, mixed>
 */
class ParamBag implements ArrayAccess, IteratorAggregate
{
    /** @var array<string, mixed> */
    public array $params = [];

    /**
     * @param string $offset
     * @param null|scalar $default
     * @return Param|null
     */
    public function getParam(string $offset, null|string|float|int|bool $default = null): ?Param
    {
        if ($this->offsetExists($offset)) {
            return $this->params[$offset];
        } elseif ($default !== null) {
            return new Param($default);
        } else {
            return null;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->params);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->params[$offset]->getValue();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_array($value) or $value instanceof ParamBag) {
            $this->params[$offset] = new Param($value);
        } else {
            $this->params[$offset] = ($value instanceof Param ? $value : new Param($value));
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->params[$offset]);
    }

    /**
     * @return array<string, Param>
     */
    public function getParamArray(): array
    {
        return $this->params;
    }

    /**
     * @param array<string, Param> $paramArray
     * @return $this
     */
    public function setParamArray(array $paramArray): static
    {
        $this->params = $paramArray;
        return $this;
    }

    /**
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->params);
    }
}