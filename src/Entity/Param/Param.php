<?php
declare(strict_types=1);

namespace LotGD2\Entity\Param;

use LotGD2\Entity\Param\ParamTypeEnum;
use TypeError;

class Param
{
    /**
     * @param int|float|bool|string|ParamBag|array<mixed> $value
     */
    public function __construct(
        private int|float|bool|string|ParamBag|array $value,
        private ?ParamTypeEnum $paramType = null,
    ) {
        if ($this->paramType !== null) {
            $this->assertParamType();
        } else {
            $this->paramType = match(get_debug_type($this->value)) {
                "bool" => ParamTypeEnum::Bool,
                "int" => ParamTypeEnum::Int,
                "float" => ParamTypeEnum::Float,
                "array", ParamBag::class => ParamTypeEnum::Bag,
                default => ParamTypeEnum::String,
            };
        }

        if ($this->paramType === ParamTypeEnum::Bag and is_array($this->value)) {
            $this->value = new ParamBag();
            foreach ($value as $key => $val) {
                $this->value[$key] = new Param($val);
            }
        }
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param int|float|bool|string|ParamBag|array<mixed> $value
     */
    public function setValue(int|float|bool|string|ParamBag|array $value): void
    {
        $this->assertParamType($value);

        if ($this->paramType === ParamTypeEnum::Bag) {
            $this->value = new ParamBag();
            foreach ($value as $key => $val) {
                $this->value[$key] = new Param($val);
            }
        } else {
            $this->value = $value;
        }
    }

    public function asInt(): int
    {
        if ($this->paramType === ParamTypeEnum::Bag) {
            throw new TypeError("Parameter contains a bag and thus cannot be converted to scalar.");
        }

        return (int)$this->getValue();
    }

    public function asFloat(): float
    {
        if ($this->paramType === ParamTypeEnum::Bag) {
            throw new TypeError("Parameter contains a bag and thus cannot be converted to scalar.");
        }

        return (float)$this->getValue();
    }

    public function asString(): string
    {
        if ($this->paramType === ParamTypeEnum::Bag) {
            throw new TypeError("Parameter contains a bag and thus cannot be converted to scalar.");
        }

        return (string)$this->getValue();
    }

    public function asBool(): bool
    {
        if ($this->paramType === ParamTypeEnum::Bag) {
            throw new TypeError("Parameter contains a bag and thus cannot be converted to scalar.");
        }

        return (bool)$this->getValue();
    }

    public function getParam(string|int $param, null|bool|float|int|string $default = null): Param
    {
        if ($this->paramType !== ParamTypeEnum::Bag) {
            throw new TypeError("Parameter is not multi-dimensional.");
        }

        return $this->value->getParam($param, $default);
    }

    /**
     * @throws TypeError
     */
    private function assertParamType(null|int|float|bool|string|ParamBag $value=null): void
    {
        $throw = false;

        if ($value === null) {
            $value = $this->value;
        }

        switch ($this->paramType) {
            case ParamTypeEnum::Bool:
                if (!is_bool($value)) {
                    $throw = true;
                }
                break;

            case ParamTypeEnum::Float:
                if (!is_float($value)) {
                    $throw = true;
                }
                break;

            case ParamTypeEnum::Int:
                if (!is_int($value)) {
                    $throw = true;
                }
                break;

            case ParamTypeEnum::String:
                if (!is_string($value)) {
                    $throw = true;
                }
                break;

            case ParamTypeEnum::Bag:
                if (!($value instanceof ParamBag or is_array($value))) {
                    $throw = true;
                }
                break;
        }

        if ($throw) {
            $actualType = get_debug_type($value);
            throw new TypeError("Value type must match ParamType. {$actualType} was given, but {$this->paramType->value} was required.");
        }
    }
}