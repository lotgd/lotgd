<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Game\Battle\BattleEvent\AbstractBattleEvent;
use LotGD2\Game\Error\BattleEventError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractBattleEvent::class)]
class AbstractBattleEventTest extends TestCase
{
    private AbstractBattleEvent $battleEvent;

    public function setUp(): void
    {
        $this->battleEvent = new class(
            $this->createMock(FighterInterface::class),
            $this->createMock(FighterInterface::class),
        ) extends AbstractBattleEvent {
            public function __construct(FighterInterface $attacker, FighterInterface $defender, array $context = [])
            {
            }
        };
    }

    public function testGetContext(): void
    {
        $context = $this->battleEvent->getContext();

        $this->assertEmpty($context);
    }

    public function testBattleEventCannotBeAppliedTwice(): void
    {
        $this->battleEvent->apply();

        $this->expectException(BattleEventError::class);
        $this->battleEvent->apply();
    }

    public function testDecorationCannotBeUsedBeforeApplying(): void
    {
        $this->expectException(BattleEventError::class);
        $this->battleEvent->decorate();
    }
}
