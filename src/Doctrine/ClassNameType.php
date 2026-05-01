<?php
declare(strict_types=1);

namespace LotGD2\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class ClassNameType extends StringType
{
    public const string NAME = 'class_name';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return class-string|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        } elseif (class_exists($value)) {
            return $value;
        } else {
            return null;
        }
    }
}