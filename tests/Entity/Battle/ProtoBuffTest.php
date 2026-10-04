<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\ProtoBuff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ProtoBuff::class)]
#[UsesClass(Buff::class)]
class ProtoBuffTest extends TestCase
{
    private const array EXPECTED_ARGUMENT_NAMES = [
        'id',
        'name',
        'activatesAt',
        'rounds',
        'startMessage',
        'roundMessage',
        'endMessage',
        'effectSuccessMessage',
        'effectFailsMessage',
        'noEffectMessage',
        'newDayMessage',
        'expiresOnNewDay',
        'expiresAfterBattle',
        'badGuyRegeneration',
        'goodGuyRegeneration',
        'badGuyLifeTap',
        'goodGuyLifeTap',
        'badGuyDamageReflection',
        'goodGuyDamageReflection',
        'badGuyDamageModifier',
        'goodGuyDamageModifier',
        'badGuyAttackModifier',
        'goodGuyAttackModifier',
        'badGuyDefenseModifier',
        'goodGuyDefenseModifier',
        'badGuyInvulnerable',
        'goodGuyInvulnerable',
        'numberOfMinions',
        'minionMinBadGuyDamage',
        'minionMaxBadGuyDamage',
        'minionMinGoodGuyDamage',
        'minionMaxGoodGuyDamage',
    ];

    public function testConstants(): void
    {
        $this->assertSame(0b0001, ProtoBuff::ACTIVATES_ON_ROUNDSTART);
        $this->assertSame(0b0010, ProtoBuff::ACTIVATES_ON_ROUNDEND);
        $this->assertSame(0b0100, ProtoBuff::ACTIVATES_ON_OFFENSE_TURN);
        $this->assertSame(0b1000, ProtoBuff::ACTIVATES_ON_DEFENSE_TURN);
        $this->assertSame(0b1100, ProtoBuff::ACTIVATES_ON_BOTH_TURNS);
        $this->assertSame(0b0000, ProtoBuff::ACTIVATES_NEVER);
        $this->assertSame(0b1111, ProtoBuff::ACTIVATES_ANY);
        $this->assertSame(-1, ProtoBuff::INFINITE_ROUNDS);
    }

    public function testConstructorArgumentNamesMatchExpected(): void
    {
        $reflection = new ReflectionClass(ProtoBuff::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $actualParameterNames = array_map(
            fn(\ReflectionParameter $param) => $param->getName(),
            $constructor->getParameters()
        );

        $this->assertSame(
            self::EXPECTED_ARGUMENT_NAMES,
            $actualParameterNames,
            'ProtoBuff constructor parameter names or order do not match expected parameter names.'
        );
    }

    public function testParameterNamesAreConsistentWithBuff(): void
    {
        $protoBuffReflection = new ReflectionClass(ProtoBuff::class);
        $buffReflection = new ReflectionClass(Buff::class);

        $protoBuffConstructor = $protoBuffReflection->getConstructor();
        $buffConstructor = $buffReflection->getConstructor();

        $this->assertNotNull($protoBuffConstructor);
        $this->assertNotNull($buffConstructor);

        $protoBuffParams = $protoBuffConstructor->getParameters();
        $buffParams = $buffConstructor->getParameters();

        $this->assertLessThanOrEqual(count($buffParams), count($protoBuffParams));

        for ($i = 0; $i < count($protoBuffParams); $i++) {
            $this->assertSame(
                $buffParams[$i]->getName(),
                $protoBuffParams[$i]->getName(),
                sprintf(
                    'Parameter at index %d differs between Buff ($%s) and ProtoBuff ($%s).',
                    $i,
                    $buffParams[$i]->getName(),
                    $protoBuffParams[$i]->getName()
                )
            );
        }
    }

    public function testConstructorWithDefaultValuesUsingNamedArguments(): void
    {
        $protoBuff = new ProtoBuff(
            id: 'proto-buff-1',
            name: 'Proto Buff Test',
            activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART,
            rounds: 5
        );

        $this->assertSame('proto-buff-1', $protoBuff->id);
        $this->assertSame('Proto Buff Test', $protoBuff->name);
        $this->assertSame(ProtoBuff::ACTIVATES_ON_ROUNDSTART, $protoBuff->activatesAt);
        $this->assertSame(5, $protoBuff->rounds);
        $this->assertNull($protoBuff->startMessage);
        $this->assertNull($protoBuff->roundMessage);
        $this->assertNull($protoBuff->endMessage);
        $this->assertNull($protoBuff->effectSuccessMessage);
        $this->assertNull($protoBuff->effectFailsMessage);
        $this->assertNull($protoBuff->noEffectMessage);
        $this->assertNull($protoBuff->newDayMessage);
        $this->assertTrue($protoBuff->expiresOnNewDay);
        $this->assertFalse($protoBuff->expiresAfterBattle);
        $this->assertSame(0, $protoBuff->badGuyRegeneration);
        $this->assertSame(0, $protoBuff->goodGuyRegeneration);
        $this->assertSame(0., $protoBuff->badGuyLifeTap);
        $this->assertSame(0., $protoBuff->goodGuyLifeTap);
        $this->assertSame(0., $protoBuff->badGuyDamageReflection);
        $this->assertSame(0., $protoBuff->goodGuyDamageReflection);
        $this->assertSame(1., $protoBuff->badGuyDamageModifier);
        $this->assertSame(1., $protoBuff->goodGuyDamageModifier);
        $this->assertSame(1., $protoBuff->badGuyAttackModifier);
        $this->assertSame(1., $protoBuff->goodGuyAttackModifier);
        $this->assertSame(1., $protoBuff->badGuyDefenseModifier);
        $this->assertSame(1., $protoBuff->goodGuyDefenseModifier);
        $this->assertFalse($protoBuff->badGuyInvulnerable);
        $this->assertFalse($protoBuff->goodGuyInvulnerable);
        $this->assertSame(0, $protoBuff->numberOfMinions);
        $this->assertSame(0, $protoBuff->minionMinBadGuyDamage);
        $this->assertSame(0, $protoBuff->minionMaxBadGuyDamage);
        $this->assertSame(0, $protoBuff->minionMinGoodGuyDamage);
        $this->assertSame(0, $protoBuff->minionMaxGoodGuyDamage);
    }

    public function testConstructorWithAllNamedArgumentsNumericTypes(): void
    {
        $protoBuff = new ProtoBuff(
            id: 'proto-buff-custom',
            name: 'Custom Proto Buff',
            activatesAt: ProtoBuff::ACTIVATES_ON_OFFENSE_TURN,
            rounds: 8,
            startMessage: 'Proto Start Message',
            roundMessage: 'Proto Round Message',
            endMessage: 'Proto End Message',
            effectSuccessMessage: 'Proto Effect Success',
            effectFailsMessage: 'Proto Effect Fails',
            noEffectMessage: 'Proto No Effect',
            newDayMessage: 'Proto New Day',
            expiresOnNewDay: false,
            expiresAfterBattle: true,
            badGuyRegeneration: 20,
            goodGuyRegeneration: 30,
            badGuyLifeTap: 0.25,
            goodGuyLifeTap: 0.5,
            badGuyDamageReflection: 0.15,
            goodGuyDamageReflection: 0.35,
            badGuyDamageModifier: 1.25,
            goodGuyDamageModifier: 1.75,
            badGuyAttackModifier: 0.9,
            goodGuyAttackModifier: 1.15,
            badGuyDefenseModifier: 0.85,
            goodGuyDefenseModifier: 1.35,
            badGuyInvulnerable: true,
            goodGuyInvulnerable: true,
            numberOfMinions: 4,
            minionMinBadGuyDamage: 3,
            minionMaxBadGuyDamage: 9,
            minionMinGoodGuyDamage: 1,
            minionMaxGoodGuyDamage: 6
        );

        $this->assertSame('proto-buff-custom', $protoBuff->id);
        $this->assertSame('Custom Proto Buff', $protoBuff->name);
        $this->assertSame(ProtoBuff::ACTIVATES_ON_OFFENSE_TURN, $protoBuff->activatesAt);
        $this->assertSame(8, $protoBuff->rounds);
        $this->assertSame('Proto Start Message', $protoBuff->startMessage);
        $this->assertSame('Proto Round Message', $protoBuff->roundMessage);
        $this->assertSame('Proto End Message', $protoBuff->endMessage);
        $this->assertSame('Proto Effect Success', $protoBuff->effectSuccessMessage);
        $this->assertSame('Proto Effect Fails', $protoBuff->effectFailsMessage);
        $this->assertSame('Proto No Effect', $protoBuff->noEffectMessage);
        $this->assertSame('Proto New Day', $protoBuff->newDayMessage);
        $this->assertFalse($protoBuff->expiresOnNewDay);
        $this->assertTrue($protoBuff->expiresAfterBattle);
        $this->assertSame(20, $protoBuff->badGuyRegeneration);
        $this->assertSame(30, $protoBuff->goodGuyRegeneration);
        $this->assertSame(0.25, $protoBuff->badGuyLifeTap);
        $this->assertSame(0.5, $protoBuff->goodGuyLifeTap);
        $this->assertSame(0.15, $protoBuff->badGuyDamageReflection);
        $this->assertSame(0.35, $protoBuff->goodGuyDamageReflection);
        $this->assertSame(1.25, $protoBuff->badGuyDamageModifier);
        $this->assertSame(1.75, $protoBuff->goodGuyDamageModifier);
        $this->assertSame(0.9, $protoBuff->badGuyAttackModifier);
        $this->assertSame(1.15, $protoBuff->goodGuyAttackModifier);
        $this->assertSame(0.85, $protoBuff->badGuyDefenseModifier);
        $this->assertSame(1.35, $protoBuff->goodGuyDefenseModifier);
        $this->assertTrue($protoBuff->badGuyInvulnerable);
        $this->assertTrue($protoBuff->goodGuyInvulnerable);
        $this->assertSame(4, $protoBuff->numberOfMinions);
        $this->assertSame(3, $protoBuff->minionMinBadGuyDamage);
        $this->assertSame(9, $protoBuff->minionMaxBadGuyDamage);
        $this->assertSame(1, $protoBuff->minionMinGoodGuyDamage);
        $this->assertSame(6, $protoBuff->minionMaxGoodGuyDamage);
    }

    public function testConstructorWithStringExpressionArguments(): void
    {
        $protoBuff = new ProtoBuff(
            id: 'expr-proto-buff',
            name: 'Expression Buff',
            activatesAt: ProtoBuff::ACTIVATES_ON_BOTH_TURNS,
            rounds: 4,
            startMessage: 'You cast a spell on {{ badGuy.name }}.',
            roundMessage: '{{ goodGuy.name }} is pulsing with energy.',
            endMessage: 'The spell fades.',
            effectSuccessMessage: 'Hit!',
            effectFailsMessage: 'Miss!',
            noEffectMessage: 'Nothing happened.',
            newDayMessage: 'The blessing stays.',
            expiresOnNewDay: true,
            expiresAfterBattle: false,
            badGuyRegeneration: 'character.level * 2',
            goodGuyRegeneration: 'character.maxHealth * 0.1',
            badGuyLifeTap: '0.2 + character.level * 0.01',
            goodGuyLifeTap: '0.1 + character.level * 0.02',
            badGuyDamageReflection: '0.15',
            goodGuyDamageReflection: '0.25',
            badGuyDamageModifier: '1.0 + character.strength * 0.05',
            goodGuyDamageModifier: '0.8',
            badGuyAttackModifier: '0.5',
            goodGuyAttackModifier: '1.2',
            badGuyDefenseModifier: '0.7',
            goodGuyDefenseModifier: '1.3',
            badGuyInvulnerable: 'character.hasBuff("invuln")',
            goodGuyInvulnerable: 'false',
            numberOfMinions: 'round(character.level / 3)',
            minionMinBadGuyDamage: 'character.level',
            minionMaxBadGuyDamage: 'character.level * 3',
            minionMinGoodGuyDamage: '1',
            minionMaxGoodGuyDamage: '5'
        );

        $this->assertSame('expr-proto-buff', $protoBuff->id);
        $this->assertSame('Expression Buff', $protoBuff->name);
        $this->assertSame(ProtoBuff::ACTIVATES_ON_BOTH_TURNS, $protoBuff->activatesAt);
        $this->assertSame(4, $protoBuff->rounds);
        $this->assertSame('character.level * 2', $protoBuff->badGuyRegeneration);
        $this->assertSame('character.maxHealth * 0.1', $protoBuff->goodGuyRegeneration);
        $this->assertSame('0.2 + character.level * 0.01', $protoBuff->badGuyLifeTap);
        $this->assertSame('0.1 + character.level * 0.02', $protoBuff->goodGuyLifeTap);
        $this->assertSame('0.15', $protoBuff->badGuyDamageReflection);
        $this->assertSame('0.25', $protoBuff->goodGuyDamageReflection);
        $this->assertSame('1.0 + character.strength * 0.05', $protoBuff->badGuyDamageModifier);
        $this->assertSame('0.8', $protoBuff->goodGuyDamageModifier);
        $this->assertSame('0.5', $protoBuff->badGuyAttackModifier);
        $this->assertSame('1.2', $protoBuff->goodGuyAttackModifier);
        $this->assertSame('0.7', $protoBuff->badGuyDefenseModifier);
        $this->assertSame('1.3', $protoBuff->goodGuyDefenseModifier);
        $this->assertSame('character.hasBuff("invuln")', $protoBuff->badGuyInvulnerable);
        $this->assertSame('false', $protoBuff->goodGuyInvulnerable);
        $this->assertSame('round(character.level / 3)', $protoBuff->numberOfMinions);
        $this->assertSame('character.level', $protoBuff->minionMinBadGuyDamage);
        $this->assertSame('character.level * 3', $protoBuff->minionMaxBadGuyDamage);
        $this->assertSame('1', $protoBuff->minionMinGoodGuyDamage);
        $this->assertSame('5', $protoBuff->minionMaxGoodGuyDamage);
    }
}
