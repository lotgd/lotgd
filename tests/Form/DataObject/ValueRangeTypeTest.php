<?php
declare(strict_types=1);

namespace LotGD2\Tests\Form\DataObject;

use LotGD2\Entity\DataObject\OptionalIntegerType;
use LotGD2\Entity\DataObject\ValueRange;
use LotGD2\Form\DataObject\ValueRangeType;
use LotGD2\Tests\Form\ValidatorTypeTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;

#[CoversClass(ValueRangeType::class)]
#[CoversClass(OptionalIntegerType::class)]
#[CoversClass(ValueRange::class)]
#[AllowMockObjectsWithoutExpectations]
class ValueRangeTypeTest extends ValidatorTypeTestCase
{
    #[TestWith([5, 100, true])]
    public function testSubmitData(
        ?int $minimum,
        ?int $maximum,
        ?bool $validity,
    ) {
        $formData = [
            "minimum" => $minimum,
            "maximum" => $maximum,
        ];

        $model = new ValueRange();
        $form = $this->factory->create(ValueRangeType::class, $model);

        $expected = new ValueRange(
            minimum: $minimum,
            maximum: $maximum,
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($validity, $form->isValid());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitEmptyData()
    {
        $formData = [];

        $model = new ValueRange();
        $form = $this->factory->create(ValueRangeType::class, $model);

        $expected = new ValueRange(
            minimum: null,
            maximum: null,
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $model);
    }



    public function testGetBlockPrefix(): void
    {
        $form = $this->factory->create(ValueRangeType::class, new ValueRange());

        $this->assertSame("valueRange", $form->getName());
    }

    public function testDataClass(): void
    {
        $form = $this->factory->create(ValueRangeType::class, new ValueRange());

        $this->assertSame(ValueRange::class, $form->getConfig()->getDataClass());
    }
}
