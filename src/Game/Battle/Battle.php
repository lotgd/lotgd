<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BasicFighterInterface;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Battle\CurrentCharacterFighter;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Event\BattleSkillActivationEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Battle\BattleEvent\BattleEventInterface;
use LotGD2\Game\Battle\BattleEvent\DamageEvent;
use LotGD2\Game\Battle\BattleEvent\DeathEvent;
use LotGD2\Game\GameLoop;
use LotGD2\Game\Handler\BuffHandler;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-import-type BattleEventCollection from BattleEventInterface
 * @phpstan-import-type DamageEventCollection from DamageEvent
 */
class Battle
{
    const string ActionGroupBattle = "lotgd2.actionGroup.battle";
    const string ActionGroupAutoBattle = "lotgd2.actionGroup.autoBattle";
    const string FightActionAttack = "lotgd2.action.attack";
    const string FightActionFlee = "lotgd2.action.flee";
    const string FightActionAutoFive = "lotgd2.action.auto5";
    const string FightActionAutoTen = "lotgd2.action.auto10";
    const string FightActionAutoAll = "lotgd2.action.autoAll";

    const string OnAddFightActions = "lotgd2.event.battle.addFightActions";
    const string OnSkillActivationEvent = "lotgd2.event.DefaultFight.skillActivationEvent";
    const string OnFightFlee = "lotgd2.event.DefaultFight.flee";
    const string OnFightFled = "lotgd2.event.DefaultFight.fled";
    const string OnFightEnds = "lotgd2.event.DefaultFight.ends";

    const string FightOpParamValue = "fight";
    const string SurpriseActionParam = "surprise";
    const string FightActionParam = "how";
    const string FleeFightActionParamValue = "flee";
    const string SkillFightActionParamValue = "skill";
    const string SkillActionParam = "skill";
    const string RoundsActionParam = "rounds";

    public function __construct(
        private LoggerInterface $logger,
        private readonly ?Stopwatch $stopWatch,
        private EventDispatcherInterface $eventDispatcher,
        private readonly DenormalizerInterface&NormalizerInterface $normalizer,
        private readonly BattleTurn $turn,
        private readonly SceneRenderer $sceneRenderer,
        private readonly ActionService $actionService,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly DiceBagInterface $diceBag,
        private readonly BuffHandler $buffHandler,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private Character $character,
    ) {
    }

    public function start(
        BasicFighterInterface $badGuy,
        bool $isLevelAdjustmentEnabled = true,
        bool $isCriticalHitEnabled = true,
        bool $isRiposteEnabled = true,
        bool $allowFlee = true,
    ): BattleState {
        $this->logger->debug("Starting Battle");

        $badGuy = new Fighter(... $this->normalizer->normalize($badGuy, context: ["groups" => ["fighter"]]));

        $battleState = new BattleState(
            CurrentCharacterFighter::fromCharacter($this->character),
            $badGuy,
            $isLevelAdjustmentEnabled,
            $isCriticalHitEnabled,
            $isRiposteEnabled,
            $allowFlee,
        );
        $battleState->setCharacter($this->character);

        return $battleState;
    }

    /**
     * Adds actions for fighting
     * @param Stage $stage
     * @param Scene $scene
     * @param BattleState $battleState
     * @param array<string, mixed> $actionParams
     * @return void
     */
    public function addFightActions(Stage $stage, Scene $scene, BattleState $battleState, array $actionParams = []): void
    {
        $this->logger->debug("Adding Battle Actions");
        $this->actionService->resetActionGroups($stage);

        $actionGroups = [
            new ActionGroup(self::ActionGroupBattle, "Fight", -100)
                ->setActions([
                    new Action($scene, "Attack", [... $actionParams, "how" => "attack", "battleState" => $battleState], reference: self::FightActionAttack),
                ]),
            new ActionGroup(self::ActionGroupAutoBattle, "Auto", -90)
                ->setActions([
                    new Action(
                        $scene,
                        "for 5 rounds", [
                        ... $actionParams,
                        "how" => "attack",
                        "rounds" => 5,
                        "battleState" => $battleState],
                        reference: self::FightActionAutoFive,
                    ),
                    new Action(
                        $scene,
                        "for 10 rounds", [
                        ... $actionParams,
                        "how" => "attack",
                        "rounds" => 10,
                        "battleState" => $battleState],
                        reference: self::FightActionAutoTen,
                    ),
                    new Action(
                        $scene,
                        "until the bitter end", [
                        ... $actionParams,
                        "how" => "attack",
                        "rounds" => -1,
                        "battleState" => $battleState],
                        reference: self::FightActionAutoAll,
                    ),
                ])
        ];

        if ($battleState->allowFlee) {
            $actionGroups[0]->addAction(new Action($scene, "Flee", [... $actionParams, "how" => "flee", "battleState" => $battleState], reference: self::FightActionFlee));
        }

        $battleNavigationEvent = new BattleNavigationChangeEvent($this->character, $battleState, $actionGroups, $scene, $actionParams);
        $battleNavigationEvent = $this->eventDispatcher->dispatch($battleNavigationEvent, self::OnAddFightActions);

        array_map(fn (ActionGroup $actionGroup) => $stage->addActionGroup($actionGroup), $battleNavigationEvent->actionGroups);
    }

    /**
     * Fights exactly one round (1 offence and 1 defence turn)
     * @param BattleState $battleState
     * @param int $damageRound
     * @return void
     */
    public function fightOneRound(BattleState $battleState, int $damageRound): void
    {
        $this->stopWatch?->start("lotgd2.Battle.fightOneRound");

        $battleState->setCharacter($this->character);

        $goodGuyBuffs = $this->buffHandler->getBuffs($this->character);
        $badGuyBuffs = $this->buffHandler->getBuffs($battleState->badGuy);

        // Activate on round start comes before the calculation of the half-turns
        $goodGuyBuffStartEvents = $goodGuyBuffs->activate(Buff::ACTIVATES_ON_ROUNDSTART, $battleState->goodGuy, $battleState->badGuy);
        $badGuyBuffStartEvents = $badGuyBuffs->activate(Buff::ACTIVATES_ON_ROUNDSTART, $battleState->badGuy, $battleState->goodGuy);

        [$offenseTurn, $defenseTurn] = $this->turn->getHalfTurns($battleState, $goodGuyBuffs, $badGuyBuffs);

        // Only add offence turns if it's part of the current round
        $offenseTurnEvents = $damageRound & BattleTurn::DamageTurnGoodGuy ? $offenseTurn : [];

        // Only add defence turns if it's part of the current round
        $defenseTurnEvents = $damageRound & BattleTurn::DamageTurnBadGuy ? $defenseTurn : [];

        // Activate on round end.
        $goodGuyBuffEndEvents = $goodGuyBuffs->activate(Buff::ACTIVATES_ON_ROUNDEND, $battleState->goodGuy, $battleState->badGuy);
        $badGuyBuffEndEvents = $badGuyBuffs->activate(Buff::ACTIVATES_ON_ROUNDEND, $battleState->badGuy, $battleState->goodGuy);

        // Expire buffs if necessary
        $goodGuyExpiredBuffEvents = $goodGuyBuffs->expireOneRound($battleState->goodGuy, $battleState->badGuy);
        $badGuyExpiredBuffEvents = $badGuyBuffs->expireOneRound($battleState->badGuy, $battleState->goodGuy);

        // Process events
        $events = new ArrayCollection([
            ... $goodGuyBuffStartEvents,
            ... $badGuyBuffStartEvents,
            ... $offenseTurnEvents,
            ... $defenseTurnEvents,
            ... $goodGuyBuffEndEvents,
            ... $badGuyBuffEndEvents,
            ... $goodGuyExpiredBuffEvents,
            ... $badGuyExpiredBuffEvents,
        ]);

        // Filters and applies events up until the fight ends. All events after the raised DeathEvent are skipped.
        $eventsToAdd = $this->processBattleEvents($events, $battleState);

        // Post round clean-up
        $battleState->incrementRound();
        $battleState->addMessages($eventsToAdd->map(fn (BattleEventInterface $event) => $event->decorate()));
        $battleState->synchronizeToCharacter($this->logger, $goodGuyBuffs);

        $this->stopWatch?->stop("lotgd2.Battle.fightOneRound");
    }

    /**
     * @param ArrayCollection<int, BattleEventInterface> $events
     * @param BattleState $battleState
     * @return BattleEventCollection
     */
    public function processBattleEvents(ArrayCollection $events, BattleState $battleState): ArrayCollection
    {
        /** @var BattleEventCollection $eventsToAdd */
        $eventsToAdd = new ArrayCollection();

        foreach ($events as $event) {
            $event->apply();

            $eventsToAdd->add($event);

            // DEATH
            if ($battleState->goodGuy->health <= 0) {
                $eventsToAdd->add(new DeathEvent($battleState->goodGuy, $battleState->badGuy, ["victim" => $battleState->goodGuy]));
                $battleState->result = BattleStateStatusEnum::BadGuyWon;
                break;
            }

            if ($battleState->badGuy->health <= 0) {
                $deathEvent = new DeathEvent($battleState->goodGuy, $battleState->badGuy, ["victim" => $battleState->badGuy]);
                $eventsToAdd->add($deathEvent);
                $battleState->result = BattleStateStatusEnum::GoodGuyWon;
                break;
            }
        }

        return $eventsToAdd;
    }

    #[AsEventListener(GameLoop::OnBeforeSameSceneChange)]
    public function onBeforeSameSceneChange(StageChangeEvent $event): StageChangeEvent
    {
        $battleState = $event->action->getParameter("battleState");

        if (!($battleState instanceof BattleState)) {
            return $event;
        }

        // Find attachment
        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        if (!$attachment) {
            // If attachment was not found, exit event without stopping propagation.
            $this->logger->critical("Attachment not found.");
            return $event;
        }

        // Manually reset stage
        $event->stage->clearParagraphs();
        $event->stage->clearAttachments();
        $this->actionService->resetActionGroups($event->stage);

        // Make sure the changes made here are not overwritten by the default event handling.
        $event->stopPropagation();

        // What has been selected in the fight?
        $how = $event->action->getParameter(self::FightActionParam);

        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.DefaultFightTrait.FightMessage",
                text: "You are in the middle of the fight against <.{{ badGuy.name }}.>.",
                context: [
                    "badGuy" => $battleState->badGuy,
                ]
            )
        ];

        //
        if ($event->action->getParameter(self::SurpriseActionParam, false) === true) {
            $battleTurn = BattleTurn::DamageTurnGoodGuy;
        } else {
            $battleTurn = BattleTurn::DamageTurnBoth;
        }

        // Handle 'flee' options
        if ($battleState->allowFlee and $how === self::FleeFightActionParamValue) {
            // If "flee" was used, we dispatch an event and delegate its handling to somewhere else
            // But we do offer a default event listener inside the battle class with low priority
            $simpleStageEvent = new SimpleStageParameterEvent($event->stage, $event->action, $event->scene, success: false, punishment: false);
            $simpleStageEvent = $this->eventDispatcher->dispatch($simpleStageEvent, self::OnFightFlee);

            if ($simpleStageEvent->params["success"] === true) {
                // If character has fled successfully, we emit another event allowing scenes to reset navigation
                $fightFledEvent = new SimpleStageParameterEvent($event->stage, $event->action, $event->scene);
                $fightFledEvent = $this->eventDispatcher->dispatch($fightFledEvent, self::OnFightFled);

                // Return on success
                return $event;
            }

            if ($simpleStageEvent->params["punishment"] === true) {
                $battleTurn = BattleTurn::DamageTurnBadGuy;
            }
        }

        // Handle 'skill activation'
        if ($how === self::SkillFightActionParamValue) {
            $skill = $event->action->getParameter(self::SkillActionParam);

            $battleSkillActivationEvent = new BattleSkillActivationEvent($event->character, $this->buffHandler, $skill, $event->action);
            $this->eventDispatcher->dispatch($battleSkillActivationEvent, self::OnSkillActivationEvent);
        }

        $event->stage->addAttachment($attachment, data: [
            "battleState" => $battleState,
        ]);

        $rounds = $event->action->getParameter(self::RoundsActionParam) ?? 1;

        do {
            $rounds -= 1;
            $this->fightOneRound($battleState, $battleTurn);

            if ($battleState->isOver()) {
                break;
            }

            // If ¨$rounds is not 0, we continue with the next round. If $rounds is 0, we stop.
            // That means, if rounds is negative, the fight continues until someone dies.
            $anotherOne = $rounds !== 0;

            // Set battle Turn back to default to remove the 'surprised' element after the first round.
            $battleTurn = BattleTurn::DamageTurnBoth;
        } while ($anotherOne);

        if ($battleState->isOver()) {
            // First add actions, then dispatch event. Offer default actions first, then let the event listener decide what to do with this.
            $this->sceneRenderer->addActions($event->stage, $event->scene);

            $simpleStageEvent = new SimpleStageParameterEvent($event->stage, $event->action, $event->scene, battleState: $battleState);
            $simpleStageEvent = $this->eventDispatcher->dispatch($simpleStageEvent, self::OnFightEnds);
        } else {
            // Only add fight actions if the fight is not over
            $this->addFightActions($event->stage, $event->scene, $battleState, ["op" => self::FightOpParamValue]);
        }

        return $event;
    }

    #[AsEventListener(self::OnFightFlee, priority: -100)]
    public function onFightFlee(SimpleStageParameterEvent $event): SimpleStageParameterEvent
    {
        if ($event->action->getParameter(self::SurpriseActionParam, false) === true || $this->diceBag->chance(0.3333, precision: 4)) {
            $this->logger->critical("Successfully escaped from the enemy.");

            $event->params["success"] = true;
        } else {
            $event->params["success"] = false;
            $event->params["punishment"] = true;
        }

        return $event;
    }
}
