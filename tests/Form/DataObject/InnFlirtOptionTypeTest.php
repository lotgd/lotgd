<?php
declare(strict_types=1);

namespace LotGD2\Tests\Form\DataObject;

use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\DataObject\OptionalIntegerType;
use LotGD2\Entity\DataObject\ValueRange;
use LotGD2\Form\DataObject\InnFlirtOptionType;
use LotGD2\Form\DataObject\ValueRangeType;
use LotGD2\Tests\Form\ValidatorTypeTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[CoversClass(InnFlirtOptionType::class)]
#[UsesClass(InnFlirtOption::class)]
#[UsesClass(OptionalIntegerType::class)]
#[UsesClass(ValueRange::class)]
#[UsesClass(ValueRangeType::class)]
#[AllowMockObjectsWithoutExpectations]
class InnFlirtOptionTypeTest extends ValidatorTypeTestCase
{
    public function testFormFieldsConfiguration(): void
    {
        $form = $this->factory->create(InnFlirtOptionType::class);

        $expectedFields = [
            "charmRequirement" => [
                "type" => IntegerType::class,
                "label" => "Charm Requirement",
            ],
            "name" => [
                "type" => TextType::class,
                "label" => "Name of the flirt action",
                "help" => "The name is used to label the action in the UI.",
            ],
            "successMessage" => [
                "type" => TextareaType::class,
                "label" => "Success message",
                "help" => "The message displayed to the player when the flirt action succeeds.",
            ],
            "addCharmIfSuccessful" => [
                "type" => CheckboxType::class,
                "label" => "Add charm point if successful",
                "help" => "If checked, the player will receive a charm point if the flirt action succeeds.",
                "required" => false,
            ],
            "charmRewardRange" => [
                "type" => ValueRangeType::class,
                "label" => "Value range for charm reward",
                "help" => "Charm is rewarded only if the flirt action succeeds and the value is within the specified range.",
            ],
            "isExhaustiveIfSuccessful" => [
                "type" => CheckboxType::class,
                "label" => "Is exhaustive if successful",
                "help" => "If checked, the flirt action will be exhaustive if it succeeds and removes 2 turns.",
                "required" => false,
            ],
            "failureMessage" => [
                "type" => TextareaType::class,
                "label" => "Failure message",
                "help" => "The message displayed to the player when the flirt action fails.",
            ],
            "removeCharmIfFailure" => [
                "type" => CheckboxType::class,
                "label" => "Remove charm point if failure",
                "help" => "If checked, the player will lose a charm point if the flirt action fails.",
                "required" => false,
            ],
            "charmRemovalRange" => [
                "type" => ValueRangeType::class,
                "label" => "Value range for charm removal",
                "help" => "Charm is removed only if the flirt action fails and the value is within the specified range.",
            ],
        ];

        $this->assertSame(
            array_keys($expectedFields),
            array_keys(iterator_to_array($form->all())),
            "Form field names do not match expected field definitions."
        );

        foreach ($expectedFields as $fieldName => $expectedConfig) {
            $this->assertTrue($form->has($fieldName), "Field '{$fieldName}' is missing from the form.");
            $field = $form->get($fieldName);
            $config = $field->getConfig();

            $this->assertInstanceOf(
                $expectedConfig["type"],
                $config->getType()->getInnerType(),
                "Field '{$fieldName}' has unexpected inner type."
            );

            $this->assertSame($expectedConfig["label"], $config->getOption("label"), "Field '{$fieldName}' label mismatch.");

            if (isset($expectedConfig["help"])) {
                $this->assertSame($expectedConfig["help"], $config->getOption("help"), "Field '{$fieldName}' help mismatch.");
            }

            if (isset($expectedConfig["required"])) {
                $this->assertSame($expectedConfig["required"], $config->getOption("required"), "Field '{$fieldName}' required option mismatch.");
            }
        }

        $this->assertSame(InnFlirtOption::class, $form->getConfig()->getDataClass());
    }

    #[TestWith([2, "Wink", "They smile.", "They look away.", true, true, false, 0, 4, 0, 1, true])]
    #[TestWith([5, "Serenade", "They applaud.", "They boo.", false, false, true, 1, 7, 0, 3, true])]
    #[TestWith([10, "Proposal", "They say yes!", "They say no.", true, false, true, 5, 12, 2, 8, true])]
    public function testSubmitData(
        int $charmRequirement,
        string $name,
        string $successMessage,
        string $failureMessage,
        bool $addCharmIfSuccessful,
        bool $removeCharmIfFailure,
        bool $isExhaustiveIfSuccessful,
        ?int $rewardMin,
        ?int $rewardMax,
        ?int $removalMin,
        ?int $removalMax,
        bool $validity,
    ): void {
        $formData = [
            "charmRequirement" => $charmRequirement,
            "name" => $name,
            "successMessage" => $successMessage,
            "failureMessage" => $failureMessage,
            "addCharmIfSuccessful" => $addCharmIfSuccessful,
            "removeCharmIfFailure" => $removeCharmIfFailure,
            "isExhaustiveIfSuccessful" => $isExhaustiveIfSuccessful,
            "charmRewardRange" => [
                "minimum" => $rewardMin,
                "maximum" => $rewardMax,
            ],
            "charmRemovalRange" => [
                "minimum" => $removalMin,
                "maximum" => $removalMax,
            ],
        ];

        $model = new InnFlirtOption(
            charmRequirement: null,
            name: null,
            successMessage: null,
            failureMessage: null,
        );

        $form = $this->factory->create(InnFlirtOptionType::class, $model);

        $expected = new InnFlirtOption(
            charmRequirement: $charmRequirement,
            name: $name,
            successMessage: $successMessage,
            failureMessage: $failureMessage,
            addCharmIfSuccessful: $addCharmIfSuccessful,
            removeCharmIfFailure: $removeCharmIfFailure,
            isExhaustiveIfSuccessful: $isExhaustiveIfSuccessful,
            charmRewardRange: new ValueRange($rewardMin, $rewardMax),
            charmRemovalRange: new ValueRange($removalMin, $removalMax),
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($validity, $form->isValid());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitEmptyData(): void
    {
        $formData = [];

        $model = new InnFlirtOption(
            charmRequirement: null,
            name: null,
            successMessage: null,
            failureMessage: null,
        );

        $form = $this->factory->create(InnFlirtOptionType::class, $model);

        $expected = new InnFlirtOption(
            charmRequirement: null,
            name: null,
            successMessage: null,
            failureMessage: null,
            addCharmIfSuccessful: false,
            removeCharmIfFailure: false,
            isExhaustiveIfSuccessful: false,
            charmRewardRange: new ValueRange(null, null),
            charmRemovalRange: new ValueRange(null, null),
        );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $model);
    }
}
