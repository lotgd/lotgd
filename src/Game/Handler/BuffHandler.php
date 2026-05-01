<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\BuffList;
use LotGD2\Entity\Battle\FighterInterface;
use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BuffHandler
{
    const string BuffPropertyName = "buffs";

    public function __construct(
        private ?LoggerInterface $logger = null,
        private ?DiceBagInterface $diceBag = null,
    ) {
    }

    public function getBuffs(Character|FighterInterface $fighter): BuffList
    {
        if ($fighter instanceof Character) {
            $buffs = $fighter->getProperty(self::BuffPropertyName, []);

            $this->logger->debug("{$fighter}: Returns new BuffList");
        } else {
            $buffs = $fighter->kwargs[self::BuffPropertyName] ?? [];
        }

        return new BuffList(
            $this->logger ?? new NullLogger(),
            $this->diceBag ?? new DiceBag(),
            $buffs
        );
    }

    /**
     * @param Character|FighterInterface $fighter
     * @param BuffList $buffs
     * @return void
     */
    public function setBuffs(Character|FighterInterface $fighter, BuffList $buffs): void
    {
        $oldLength = count($this->getBuffs($fighter)->buffs);
        $newLength = count($buffs->buffs);
        if ($fighter instanceof Character) {
            $this->logger->debug("{$fighter}: Set BuffList (New length: {$newLength}, Old length: {$oldLength})");
            $fighter->setProperty(self::BuffPropertyName , $buffs->buffs);
        } else {
            $fighter->kwargs[self::BuffPropertyName] = $buffs->buffs;
        }
    }

    public function addBuff(Character|FighterInterface $fighter, Buff $buff): void
    {
        if ($fighter instanceof Character) {
            $fighter->setProperty(self::BuffPropertyName, array_merge($fighter->getProperty(self::BuffPropertyName, []), [$buff]));
        } else {
            $fighter->kwargs[self::BuffPropertyName][] = $buff;
        }
    }

    public function addBuffFromPrototype(Character $character, ProtoBuff $protoBuff): void
    {
        $equipmentHandler = new EquipmentHandler($this->logger, $character);
        $expressionService = new ExpressionService(
            $this->logger,
            $character,
            health: new HealthHandler($this->logger, $character),
            stats: new StatsHandler($this->logger, $equipmentHandler, $character),
            gold: new GoldHandler($this->logger, $character),
            equipment: $equipmentHandler,
        );

        $buff = new Buff(
            id: $protoBuff->id,
            name: $protoBuff->name,
            activatesAt: $protoBuff->activatesAt,
            rounds: $protoBuff->rounds,
            startMessage: $protoBuff->startMessage,
            roundMessage: $protoBuff->roundMessage,
            endMessage: $protoBuff->endMessage,
            effectSuccessMessage: $protoBuff->effectSuccessMessage,
            effectFailsMessage: $protoBuff->effectFailsMessage,
            noEffectMessage: $protoBuff->noEffectMessage,
            newDayMessage: $protoBuff->newDayMessage,
            expiresOnNewDay: $protoBuff->expiresOnNewDay,
            expiresAfterBattle: $protoBuff->expiresAfterBattle,
            badGuyRegeneration: is_string($protoBuff->badGuyRegeneration) ? $expressionService->evaluateInteger($protoBuff->badGuyRegeneration) : $protoBuff->badGuyRegeneration,
            goodGuyRegeneration: is_string($protoBuff->goodGuyRegeneration) ? $expressionService->evaluateInteger($protoBuff->goodGuyRegeneration) : $protoBuff->goodGuyRegeneration,
            badGuyLifeTap: is_string($protoBuff->badGuyLifeTap) ? $expressionService->evaluateFloat($protoBuff->badGuyLifeTap, 0) : $protoBuff->badGuyLifeTap,
            goodGuyLifeTap: is_string($protoBuff->goodGuyLifeTap) ? $expressionService->evaluateFloat($protoBuff->goodGuyLifeTap, 0) : $protoBuff->goodGuyLifeTap,
            badGuyDamageReflection: is_string($protoBuff->badGuyDamageReflection) ? $expressionService->evaluateFloat($protoBuff->badGuyDamageReflection, 0) : $protoBuff->badGuyDamageReflection,
            goodGuyDamageReflection: is_string($protoBuff->goodGuyDamageReflection) ? $expressionService->evaluateFloat($protoBuff->goodGuyDamageReflection, 0) : $protoBuff->goodGuyDamageReflection,
            badGuyDamageModifier: is_string($protoBuff->badGuyDamageModifier) ? $expressionService->evaluateFloat($protoBuff->badGuyDamageModifier, 1) : $protoBuff->badGuyDamageModifier,
            goodGuyDamageModifier: is_string($protoBuff->goodGuyDamageModifier) ? $expressionService->evaluateFloat($protoBuff->goodGuyDamageModifier, 1) : $protoBuff->goodGuyDamageModifier,
            badGuyAttackModifier: is_string($protoBuff->badGuyAttackModifier) ? $expressionService->evaluateFloat($protoBuff->badGuyAttackModifier, 1) : $protoBuff->badGuyAttackModifier,
            goodGuyAttackModifier: is_string($protoBuff->goodGuyAttackModifier) ? $expressionService->evaluateFloat($protoBuff->goodGuyAttackModifier, 1) : $protoBuff->goodGuyAttackModifier,
            badGuyDefenseModifier: is_string($protoBuff->badGuyDefenseModifier) ? $expressionService->evaluateFloat($protoBuff->badGuyDefenseModifier, 1) : $protoBuff->badGuyDefenseModifier,
            goodGuyDefenseModifier: is_string($protoBuff->goodGuyDefenseModifier) ? $expressionService->evaluateFloat($protoBuff->goodGuyDefenseModifier, 1) : $protoBuff->goodGuyDefenseModifier,
            badGuyInvulnerable: is_string($protoBuff->badGuyInvulnerable) ? $expressionService->evaluateBoolean($protoBuff->badGuyInvulnerable, false) : $protoBuff->badGuyInvulnerable,
            goodGuyInvulnerable: is_string($protoBuff->goodGuyInvulnerable) ? $expressionService->evaluateBoolean($protoBuff->goodGuyInvulnerable, false) : $protoBuff->goodGuyInvulnerable,
            numberOfMinions: is_string($protoBuff->numberOfMinions) ?  $expressionService->evaluateInteger($protoBuff->numberOfMinions, 0) : $protoBuff->numberOfMinions,
            minionMinBadGuyDamage: is_string($protoBuff->minionMinBadGuyDamage) ? $expressionService->evaluateInteger($protoBuff->minionMinBadGuyDamage, 0) : $protoBuff->minionMinBadGuyDamage,
            minionMaxBadGuyDamage: is_string($protoBuff->minionMaxBadGuyDamage) ? $expressionService->evaluateInteger($protoBuff->minionMaxBadGuyDamage, 0) : $protoBuff->minionMaxBadGuyDamage,
            minionMinGoodGuyDamage: is_string($protoBuff->minionMinGoodGuyDamage) ? $expressionService->evaluateInteger($protoBuff->minionMinGoodGuyDamage, 0) : $protoBuff->minionMinGoodGuyDamage,
            minionMaxGoodGuyDamage: is_string($protoBuff->minionMaxGoodGuyDamage) ? $expressionService->evaluateInteger($protoBuff->minionMaxGoodGuyDamage, 0) : $protoBuff->minionMaxGoodGuyDamage,
        );


        $this->addBuff($character, $buff);
    }
}