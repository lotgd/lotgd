<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Battle;

use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\ProtoBuff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Buff::class)]
#[UsesClass(ProtoBuff::class)]
class BuffTest extends TestCase
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
        'hasBeenStarted',
        'roundsUsed',
    ];

    public function testConstants(): void
    {
        $this->assertSame(0b0001, Buff::ACTIVATES_ON_ROUNDSTART);
        $this->assertSame(0b0010, Buff::ACTIVATES_ON_ROUNDEND);
        $this->assertSame(0b0100, Buff::ACTIVATES_ON_OFFENSE_TURN);
        $this->assertSame(0b1000, Buff::ACTIVATES_ON_DEFENSE_TURN);
        $this->assertSame(0b1100, Buff::ACTIVATES_ON_BOTH_TURNS);
        $this->assertSame(0b0000, Buff::ACTIVATES_NEVER);
        $this->assertSame(0b1111, Buff::ACTIVATES_ANY);
        $this->assertSame(-1, Buff::INFINITE_ROUNDS);
    }

    public function testConstructorArgumentNamesMatchExpected(): void
    {
        $reflection = new ReflectionClass(Buff::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $actualParameterNames = array_map(
            fn(\ReflectionParameter $param) => $param->getName(),
            $constructor->getParameters()
        );

        $this->assertSame(
            self::EXPECTED_ARGUMENT_NAMES,
            $actualParameterNames,
            'Buff constructor parameter names or order do not match expected parameter names.'
        );
    }

    public function testParameterNamesAreConsistentWithProtoBuff(): void
    {
        $buffReflection = new ReflectionClass(Buff::class);
        $protoBuffReflection = new ReflectionClass(ProtoBuff::class);

        $buffConstructor = $buffReflection->getConstructor();
        $protoBuffConstructor = $protoBuffReflection->getConstructor();

        $this->assertNotNull($buffConstructor);
        $this->assertNotNull($protoBuffConstructor);

        $buffParams = $buffConstructor->getParameters();
        $protoBuffParams = $protoBuffConstructor->getParameters();

        $protoBuffParamNames = array_map(
            fn(\ReflectionParameter $param) => $param->getName(),
            $protoBuffParams
        );

        // Buff should contain all parameters from ProtoBuff in the exact same order
        for ($i = 0; $i < count($protoBuffParams); $i++) {
            $this->assertSame(
                $protoBuffParams[$i]->getName(),
                $buffParams[$i]->getName(),
                sprintf(
                    'Parameter at index %d differs between ProtoBuff ($%s) and Buff ($%s).',
                    $i,
                    $protoBuffParams[$i]->getName(),
                    $buffParams[$i]->getName()
                )
            );
        }

        // Additional parameters in Buff must be explicit
        $buffOnlyParamNames = array_slice(
            array_map(fn(\ReflectionParameter $p) => $p->getName(), $buffParams),
            count($protoBuffParams)
        );

        $this->assertSame(['hasBeenStarted', 'roundsUsed'], $buffOnlyParamNames);
    }

    public function testConstructorWithDefaultValuesUsingNamedArguments(): void
    {
        $buff = new Buff(
            id: 'buff-id-1',
            name: 'Test Buff',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 3
        );

        $this->assertSame('buff-id-1', $buff->id);
        $this->assertSame('Test Buff', $buff->name);
        $this->assertSame(Buff::ACTIVATES_ON_ROUNDSTART, $buff->activatesAt);
        $this->assertSame(3, $buff->rounds);
        $this->assertNull($buff->startMessage);
        $this->assertNull($buff->roundMessage);
        $this->assertNull($buff->endMessage);
        $this->assertNull($buff->effectSuccessMessage);
        $this->assertNull($buff->effectFailsMessage);
        $this->assertNull($buff->noEffectMessage);
        $this->assertNull($buff->newDayMessage);
        $this->assertTrue($buff->expiresOnNewDay);
        $this->assertFalse($buff->expiresAfterBattle);
        $this->assertSame(0, $buff->badGuyRegeneration);
        $this->assertSame(0, $buff->goodGuyRegeneration);
        $this->assertSame(0., $buff->badGuyLifeTap);
        $this->assertSame(0., $buff->goodGuyLifeTap);
        $this->assertSame(0., $buff->badGuyDamageReflection);
        $this->assertSame(0., $buff->goodGuyDamageReflection);
        $this->assertSame(1., $buff->badGuyDamageModifier);
        $this->assertSame(1., $buff->goodGuyDamageModifier);
        $this->assertSame(1., $buff->badGuyAttackModifier);
        $this->assertSame(1., $buff->goodGuyAttackModifier);
        $this->assertSame(1., $buff->badGuyDefenseModifier);
        $this->assertSame(1., $buff->goodGuyDefenseModifier);
        $this->assertFalse($buff->badGuyInvulnerable);
        $this->assertFalse($buff->goodGuyInvulnerable);
        $this->assertSame(0, $buff->numberOfMinions);
        $this->assertSame(0, $buff->minionMinBadGuyDamage);
        $this->assertSame(0, $buff->minionMaxBadGuyDamage);
        $this->assertSame(0, $buff->minionMinGoodGuyDamage);
        $this->assertSame(0, $buff->minionMaxGoodGuyDamage);
        $this->assertFalse($buff->hasBeenStarted);
        $this->assertSame(0, $buff->roundsUsed);
    }

    public function testConstructorWithAllNamedArguments(): void
    {
        $buff = new Buff(
            id: 'custom-buff',
            name: 'Custom Buff Name',
            activatesAt: Buff::ACTIVATES_ON_OFFENSE_TURN,
            rounds: 10,
            startMessage: 'Start Message',
            roundMessage: 'Round Message',
            endMessage: 'End Message',
            effectSuccessMessage: 'Success Message',
            effectFailsMessage: 'Fails Message',
            noEffectMessage: 'No Effect Message',
            newDayMessage: 'New Day Message',
            expiresOnNewDay: false,
            expiresAfterBattle: true,
            badGuyRegeneration: 15,
            goodGuyRegeneration: 25,
            badGuyLifeTap: 0.15,
            goodGuyLifeTap: 0.35,
            badGuyDamageReflection: 0.25,
            goodGuyDamageReflection: 0.45,
            badGuyDamageModifier: 1.5,
            goodGuyDamageModifier: 1.8,
            badGuyAttackModifier: 0.8,
            goodGuyAttackModifier: 1.2,
            badGuyDefenseModifier: 0.7,
            goodGuyDefenseModifier: 1.4,
            badGuyInvulnerable: true,
            goodGuyInvulnerable: true,
            numberOfMinions: 3,
            minionMinBadGuyDamage: 5,
            minionMaxBadGuyDamage: 12,
            minionMinGoodGuyDamage: 2,
            minionMaxGoodGuyDamage: 8,
            hasBeenStarted: true,
            roundsUsed: 2
        );

        $this->assertSame('custom-buff', $buff->id);
        $this->assertSame('Custom Buff Name', $buff->name);
        $this->assertSame(Buff::ACTIVATES_ON_OFFENSE_TURN, $buff->activatesAt);
        $this->assertSame(10, $buff->rounds);
        $this->assertSame('Start Message', $buff->startMessage);
        $this->assertSame('Round Message', $buff->roundMessage);
        $this->assertSame('End Message', $buff->endMessage);
        $this->assertSame('Success Message', $buff->effectSuccessMessage);
        $this->assertSame('Fails Message', $buff->effectFailsMessage);
        $this->assertSame('No Effect Message', $buff->noEffectMessage);
        $this->assertSame('New Day Message', $buff->newDayMessage);
        $this->assertFalse($buff->expiresOnNewDay);
        $this->assertTrue($buff->expiresAfterBattle);
        $this->assertSame(15, $buff->badGuyRegeneration);
        $this->assertSame(25, $buff->goodGuyRegeneration);
        $this->assertSame(0.15, $buff->badGuyLifeTap);
        $this->assertSame(0.35, $buff->goodGuyLifeTap);
        $this->assertSame(0.25, $buff->badGuyDamageReflection);
        $this->assertSame(0.45, $buff->goodGuyDamageReflection);
        $this->assertSame(1.5, $buff->badGuyDamageModifier);
        $this->assertSame(1.8, $buff->goodGuyDamageModifier);
        $this->assertSame(0.8, $buff->badGuyAttackModifier);
        $this->assertSame(1.2, $buff->goodGuyAttackModifier);
        $this->assertSame(0.7, $buff->badGuyDefenseModifier);
        $this->assertSame(1.4, $buff->goodGuyDefenseModifier);
        $this->assertTrue($buff->badGuyInvulnerable);
        $this->assertTrue($buff->goodGuyInvulnerable);
        $this->assertSame(3, $buff->numberOfMinions);
        $this->assertSame(5, $buff->minionMinBadGuyDamage);
        $this->assertSame(12, $buff->minionMaxBadGuyDamage);
        $this->assertSame(2, $buff->minionMinGoodGuyDamage);
        $this->assertSame(8, $buff->minionMaxGoodGuyDamage);
        $this->assertTrue($buff->hasBeenStarted);
        $this->assertSame(2, $buff->roundsUsed);
    }

    public function testPublicPropertiesCanBeModified(): void
    {
        $buff = new Buff(
            id: 'original-id',
            name: 'Original Name',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 5
        );

        $this->assertFalse($buff->hasBeenStarted);
        $buff->hasBeenStarted = true;
        $this->assertTrue($buff->hasBeenStarted);
    }

    public function testConsumeRoundIncrementsRoundsUsed(): void
    {
        $buff = new Buff(
            id: 'test',
            name: 'Test',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 5
        );

        $this->assertSame(0, $buff->roundsUsed);

        $buff->consumeRound();
        $this->assertSame(1, $buff->roundsUsed);

        $buff->consumeRound();
        $this->assertSame(2, $buff->roundsUsed);

        $buff->consumeRound(5);
        $this->assertSame(3, $buff->roundsUsed);
    }

    public function testIsExpiredWithPositiveRounds(): void
    {
        $buff = new Buff(
            id: 'test',
            name: 'Test',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 2
        );

        $this->assertFalse($buff->isExpired());

        $buff->consumeRound();
        $this->assertFalse($buff->isExpired());

        $buff->consumeRound();
        $this->assertTrue($buff->isExpired());

        $buff->consumeRound();
        $this->assertTrue($buff->isExpired());
    }

    public function testIsExpiredWithZeroRounds(): void
    {
        $buff = new Buff(
            id: 'test',
            name: 'Test',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: 0
        );

        $this->assertTrue($buff->isExpired());
    }

    public function testIsExpiredWithInfiniteRounds(): void
    {
        $buff = new Buff(
            id: 'test',
            name: 'Test',
            activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
            rounds: Buff::INFINITE_ROUNDS
        );

        $this->assertFalse($buff->isExpired());

        $buff->consumeRound();
        $buff->consumeRound();
        $buff->consumeRound();

        $this->assertSame(3, $buff->roundsUsed);
        $this->assertFalse($buff->isExpired());
    }

    #[TestWith([Buff::ACTIVATES_NEVER, Buff::ACTIVATES_NEVER, false])]
    #[TestWith([Buff::ACTIVATES_NEVER, Buff::ACTIVATES_ON_ROUNDSTART, false])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_NEVER, false])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_ON_ROUNDSTART, true])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_ON_ROUNDEND, false])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_ON_OFFENSE_TURN, false])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDSTART, Buff::ACTIVATES_ON_DEFENSE_TURN, false])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND, Buff::ACTIVATES_ON_ROUNDEND, true])]
    #[TestWith([Buff::ACTIVATES_ON_ROUNDEND, Buff::ACTIVATES_ON_ROUNDSTART, false])]
    #[TestWith([Buff::ACTIVATES_ON_OFFENSE_TURN, Buff::ACTIVATES_ON_OFFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ON_DEFENSE_TURN, Buff::ACTIVATES_ON_DEFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ON_BOTH_TURNS, Buff::ACTIVATES_ON_OFFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ON_BOTH_TURNS, Buff::ACTIVATES_ON_DEFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ON_BOTH_TURNS, Buff::ACTIVATES_ON_ROUNDSTART, false])]
    #[TestWith([Buff::ACTIVATES_ANY, Buff::ACTIVATES_ON_ROUNDSTART, true])]
    #[TestWith([Buff::ACTIVATES_ANY, Buff::ACTIVATES_ON_ROUNDEND, true])]
    #[TestWith([Buff::ACTIVATES_ANY, Buff::ACTIVATES_ON_OFFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ANY, Buff::ACTIVATES_ON_DEFENSE_TURN, true])]
    #[TestWith([Buff::ACTIVATES_ANY, Buff::ACTIVATES_NEVER, false])]
    public function testGetsActivatedAt(int $buffActivatesAt, int $checkFlag, bool $expected): void
    {
        $buff = new Buff(
            id: 'test',
            name: 'Test',
            activatesAt: $buffActivatesAt,
            rounds: 3
        );

        $this->assertSame($expected, $buff->getsActivatedAt($checkFlag));
    }
}
