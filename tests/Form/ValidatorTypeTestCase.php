<?php
declare(strict_types=1);

namespace LotGD2\Tests\Form;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ValidatorTypeTestCase extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }
}