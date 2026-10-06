<?php
declare(strict_types=1);

namespace LotGD2\Tests\Form;

use LotGD2\Form\TabbedType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TabbedType::class)]
#[AllowMockObjectsWithoutExpectations]
class TabbedTypeTest extends ValidatorTypeTestCase
{
    public function testGetBlockPrefix(): void
    {
        $form = $this->factory->create(TabbedType::class);

        $this->assertSame("tabbed", $form->getName());
    }
}
