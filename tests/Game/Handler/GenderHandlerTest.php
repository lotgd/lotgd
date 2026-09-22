<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Handler;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Game\Handler\GenderHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

#[CoversClass(GenderHandler::class)]
#[UsesClass(Character::class)]
#[UsesClass(FormExtensionEvent::class)]
class GenderHandlerTest extends TestCase
{
    public function testIfPreferredPronounIsOtherIfPropertyIsNotSet(): void
    {
        $genderHandler = new GenderHandler();
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(GenderHandler::GenderProperty)
            ->willReturn(null);

        $this->assertSame("other", $genderHandler->getPreferredPronouns($character));
    }

    #[TestWith(["male", "male"])]
    #[TestWith(["female", "female"])]
    #[TestWith(["other", "other"])]
    #[TestWith(["hybrid", "other"])]
    #[TestWith(["Male", "other"])]
    #[TestWith([null, "other"])]
    public function testIfPreferredPronounIsOtherIfPropertyIsSetAndPronounFieldIsSet(
        mixed $set,
        string $expected,
    ): void {
        $genderHandler = new GenderHandler();
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(GenderHandler::GenderProperty)
            ->willReturn([
                GenderHandler::PronounsGenderProperty => $set,
            ]);

        $this->assertSame($expected, $genderHandler->getPreferredPronouns($character));
    }

    public function testIfOnCharacterEditAddsFields(): void
    {
        $genderHandler = new GenderHandler();
        $builder = $this->createMock(FormBuilderInterface::class);
        $innerBuilder = $this->createStub(FormBuilderInterface::class);

        $builder->expects($this->atLeastOnce())
            ->method('add')
            ->with($innerBuilder)
        ;

        $builder->expects($this->atLeastOnce())
            ->method("create")
            ->willReturnCallback(function ($name, $type, $options) use ($innerBuilder) {
                $this->assertSame(GenderHandler::GenderProperty, $name);
                return $innerBuilder;
            })
        ;

        /** @var FormExtensionEvent&Stub $event */
        $event = $this->getStubBuilder(FormExtensionEvent::class)
            ->setConstructorArgs([$builder, []])
            ->getStub();
        ;

        $genderHandler->onCharacterEdit($event);
    }

    public function testIfPreferredPartnerIsMaleIfPropertyIsNotSet(): void
    {
        $genderHandler = new GenderHandler();
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(GenderHandler::GenderProperty)
            ->willReturn(null);

        $this->assertSame("male", $genderHandler->getPreferredPartner($character));
    }

    #[TestWith(["male", "male"])]
    #[TestWith(["female", "female"])]
    #[TestWith(["other", "male"])]
    #[TestWith(["hybrid", "male"])]
    #[TestWith(["Male", "male"])]
    #[TestWith([null, "male"])]
    public function testIfPreferredPartnerIsExpectedValueIfPropertyIsSetAndPartnerFieldIsSet(
        mixed $set,
        string $expected,
    ): void {
        $genderHandler = new GenderHandler();
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method("getProperty")
            ->with(GenderHandler::GenderProperty)
            ->willReturn([
                GenderHandler::PartnerGenderProperty => $set,
            ]);

        $this->assertSame($expected, $genderHandler->getPreferredPartner($character));
    }
}
