<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Character;

use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Character\SpecialtySkill;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecialtySkill::class)]
#[UsesClass(ProtoBuff::class)]
class SpecialtySkillTest extends TestCase
{
    #[TestWith(["name", 3, new ProtoBuff(id: "1", name: "buff", activatesAt: Buff::ACTIVATES_ON_OFFENSE_TURN, rounds: 3)])]
    #[TestWith(["otherName", 7, new ProtoBuff(id: "asd", name: "buff", activatesAt: Buff::ACTIVATES_NEVER, rounds: -1)])]
    public function testConstructor(
        $name,
        $costs,
        $buff,
    ) {
        $skill = new SpecialtySkill(
            name: $name,
            costs: $costs,
            buff: $buff,
        );

        $this->assertSame($name, $skill->name);
        $this->assertSame($costs, $skill->costs);
        $this->assertSame($buff, $skill->buff);
    }
}
