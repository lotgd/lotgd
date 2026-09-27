<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\DataObject;

use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\DataObject\ValueRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InnFlirtOption::class)]
#[UsesClass(ValueRange::class)]
class InnFlirtOptionTest extends TestCase
{
    public function testConstructorWithDefaultRangesAndFlags(): void
    {
        $option = new InnFlirtOption(
            charmRequirement: 5,
            name: "Wink",
            successMessage: "They smile back.",
            failureMessage: "They look away.",
        );

        $this->assertSame(5, $option->charmRequirement);
        $this->assertSame("Wink", $option->name);
        $this->assertSame("They smile back.", $option->successMessage);
        $this->assertSame("They look away.", $option->failureMessage);
        $this->assertTrue($option->addCharmIfSuccessful);
        $this->assertTrue($option->removeCharmIfFailure);
        $this->assertFalse($option->isExhaustiveIfSuccessful);

        $this->assertInstanceOf(ValueRange::class, $option->charmRewardRange);
        $this->assertSame(0, $option->charmRewardRange->minimum);
        $this->assertSame(7, $option->charmRewardRange->maximum);

        $this->assertInstanceOf(ValueRange::class, $option->charmRemovalRange);
        $this->assertSame(0, $option->charmRemovalRange->minimum);
        $this->assertSame(4, $option->charmRemovalRange->maximum);
    }

    public function testConstructorWithExplicitRangesPreservesThem(): void
    {
        $rewardRange = new ValueRange(3, 10);
        $removalRange = new ValueRange(1, 3);

        $option = new InnFlirtOption(
            charmRequirement: 5,
            name: "Serenade",
            successMessage: "Music charms them.",
            failureMessage: "Your voice cracks.",
            addCharmIfSuccessful: false,
            removeCharmIfFailure: false,
            isExhaustiveIfSuccessful: true,
            charmRewardRange: $rewardRange,
            charmRemovalRange: $removalRange,
        );

        $this->assertSame(5, $option->charmRequirement);
        $this->assertSame("Serenade", $option->name);
        $this->assertSame("Music charms them.", $option->successMessage);
        $this->assertSame("Your voice cracks.", $option->failureMessage);
        $this->assertFalse($option->addCharmIfSuccessful);
        $this->assertFalse($option->removeCharmIfFailure);
        $this->assertTrue($option->isExhaustiveIfSuccessful);
        $this->assertSame($rewardRange, $option->charmRewardRange);
        $this->assertSame($removalRange, $option->charmRemovalRange);
    }

    public function testConstructorWithNullCharmRequirementLeavesRangesNull(): void
    {
        $option = new InnFlirtOption(
            charmRequirement: null,
            name: "Compliment",
            successMessage: "Thanks!",
            failureMessage: "No thanks.",
        );

        $this->assertNull($option->charmRequirement);
        $this->assertNull($option->charmRewardRange);
        $this->assertNull($option->charmRemovalRange);
    }

    public function testConstructorWithAllNullArguments(): void
    {
        $option = new InnFlirtOption(
            charmRequirement: null,
            name: null,
            successMessage: null,
            failureMessage: null,
            addCharmIfSuccessful: null,
            removeCharmIfFailure: null,
            isExhaustiveIfSuccessful: null,
            charmRewardRange: null,
            charmRemovalRange: null,
        );

        $this->assertNull($option->charmRequirement);
        $this->assertNull($option->name);
        $this->assertNull($option->successMessage);
        $this->assertNull($option->failureMessage);
        $this->assertNull($option->addCharmIfSuccessful);
        $this->assertNull($option->removeCharmIfFailure);
        $this->assertNull($option->isExhaustiveIfSuccessful);
        $this->assertNull($option->charmRewardRange);
        $this->assertNull($option->charmRemovalRange);
    }
}
