<?php
declare(strict_types=1);

namespace LotGD2\Tests\Form\DataObject;

use LotGD2\Entity\DataObject\Chance;
use LotGD2\Form\DataObject\ChanceType;
use LotGD2\Form\TabbedType;
use LotGD2\Tests\Form\ValidatorTypeTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ChanceType::class)]
#[UsesClass(Chance::class)]
#[AllowMockObjectsWithoutExpectations]
class ChanceTypeTest extends ValidatorTypeTestCase
{
    #[TestWith([5, 100, true])]
    #[TestWith([-5, 100, false])]
    #[TestWith([0, 0, false])]
    #[TestWith([0, 1, true])]
    #[TestWith([null, null, false])]
    #[TestWith([null, 100, false])]
    #[TestWith([100, null, false])]
    #[TestWith([5, null, false])]
    #[TestWith([5, -100, false])]
    #[TestWith([0, 0, false])]
    public function testSubmitData(
        ?int $numerator,
        ?int $denominator,
        ?bool $validity,
    ) {
        $formData = [
            "numerator" => $numerator,
            "denominator" => $denominator,
        ];

        $model = new Chance();
        $form = $this->factory->create(ChanceType::class, $model);

        $expected = new Chance(
            numerator: $numerator,
            denominator: $denominator,
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($validity, $form->isValid());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitSingleValue()
    {
        $formData = [

        ];

        $model = 5;
        $form = $this->factory->create(ChanceType::class, $model);

        $expected = new Chance(
            numerator: 5,
            denominator: 100,
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testGetBlockPrefix(): void
    {
        $form = $this->factory->create(ChanceType::class, 0);

        $this->assertSame("chance", $form->getName());
    }

    public function testDataClass(): void
    {
        $form = $this->factory->create(ChanceType::class, 0);

        $this->assertSame(Chance::class, $form->getConfig()->getDataClass());
    }
}
