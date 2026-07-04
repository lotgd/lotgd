<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\LootBagEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\StatsHandler;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

trait DefaultFightTrait
{
    private readonly GoldHandler $gold;
    private readonly StatsHandler $stats;
    private readonly EventDispatcherInterface $eventDispatcher;

    const string OnLootBagFill = "lotgd2.event.DefaultFight.lootBagFill";
    const string OnLootBagClaim = "lotgd2.event.DefaultFight.lootBagClaim";
    const string OnSkillActivationEvent = "lotgd2.event.DefaultFight.skillActivationEvent";

    /**
     * Redefine what happens if the battle state disappears
     * @return void
     */
    public function onBattleStateDisappeared(): void
    {
        $this->addDefaultActions($this->getStage(), $this->getScene());
        $this->getStage()->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.DefaultFightTrait.SuddenFightEnd",
                text: "The battle suddenly ended.",
            )
        ];
    }

    abstract public function getStage(): Stage;
    abstract public function getScene(): Scene;

    /**
     * Whenever a fight ends (expected or unexpected), the state can be reused to search for the next fight. This
     * method offers a hook to add additional default actions (besides the usual scene connections).
     * @param Stage $stage
     * @param Scene $scene
     * @return void
     */
    abstract public function addDefaultActions(Stage $stage, Scene $scene): void;

    #[AsEventListener(Battle::OnFightFled, priority: -50)]
    public function onFightFled(SimpleStageParameterEvent $event): void
    {
        if ($event->scene->templateClass !== self::class) {
            // Only catch events on scenes with the current class
            // Multiple scenes are using this trait, so there will be multiple classes listening in automatically
            return;
        }

        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.DefaultFightTrait.onFlightFlee",
                text: "You have successfully fled your opponent!",
            ),
            new Paragraph(
                id: Stage::SceneText,
                text: $this->scene->description,
            )
        ];

        // Add standard navigation
        $this->addDefaultActions($event->stage, $event->scene);
    }

    /**
     * Gets called whenever a fight is over.
     *
     * If no special code is necessary, decisions can also be shifted to onFightWon and onFightList to only change
     * what happens in these specific cases.
     * @param SimpleStageParameterEvent $event
     * @return void
     */
    #[AsEventListener(Battle::OnFightEnds, priority: -50)]
    public function onFightEnds(SimpleStageParameterEvent $event): void
    {
        if ($event->scene->templateClass !== self::class) {
            // Only catch events on scenes with the current class
            // Multiple scenes are using this trait, so there will be multiple classes listening in automatically
            return;
        }

        /** @var BattleState $battleState */
        $battleState = $event->params["battleState"];

        if ($battleState->result === BattleStateStatusEnum::GoodGuyWon) {
            $this->onFightWon($event, $battleState);
        } else {
            $this->onFightLost($event, $battleState);
        }

        // Add standard navigation if battle is over
        $this->addDefaultActions($event->stage, $event->scene);
    }

    /**
     * Handles the event triggered when a fight is won by the character.
     *
     * @param SimpleStageParameterEvent $event
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(SimpleStageParameterEvent $event, BattleState $battleState): void
    {
        // Only dispatch event and create loot if the event dispatcher has been set.
        //   phpstan ignore flag is necessary due to phpstan complaining that eventDispatcher cannot be null
        //   although this is true, it can be unset of the class using the trait does not set it.
        if (isset($this->eventDispatcher)) { // @phpstan-ignore isset.property
            $lootBagEvent = new LootBagEvent($battleState);
            $lootBagEvent = $this->eventDispatcher->dispatch($lootBagEvent, self::OnLootBagFill);
        } else {
            $this->logger->info("No LootBagEvent dispatched as the EventDispatcher was not set in the class using this trait.");
            $lootBagEvent = null;
        }

        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.DefaultFightTrait.onFightWon",
                text:<<<TEXT
                    You have slain <.{{ badGuy.name }}.>. {% if textDefeated %}<<{{ textDefeated }}>>{% endif %}
                    TEXT,
                context: [
                    "badGuy" => $battleState->badGuy,
                    "textDefeated" => $battleState->badGuy->kwargs["textDefeated"] ?? null,
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            ),
        ];

        if ($lootBagEvent instanceof LootBagEvent) {
            $lootBag = $lootBagEvent->lootBag;
            $lootBag->lock();
            $lootBagEvent = new LootBagEvent($battleState, $lootBag, $event->stage);
            $this->eventDispatcher->dispatch($lootBagEvent, self::OnLootBagClaim);
        } else {
            $this->logger->critical("Loot bag event disappeared.", ["event", $lootBagEvent]);
        }
    }

    /**
     * Handles the event triggered when a fight is lost by the character.
     *
     * Updates the context with information about the gold and experience lost during the fight.
     * Sets the stage description to reflect that the fight has been lost, detailing the penalties incurred.
     * Logs the event and adjusts the gold and experience of the character to represent the loss.
     *
     * @param SimpleStageParameterEvent $event
     * @param BattleState $battleState The state of the battle at the time of the loss.
     *
     * @return void
     */
    public function onFightLost(SimpleStageParameterEvent $event, BattleState $battleState): void
    {
        $experienceLost = (int)round(0.1 * $this->stats->getExperience());

        $event->stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.DefaultFightTrait.onFightLost",
                text: <<<TEXT
                    You have been slain by <.{{ badGuy.name }}.>. {% if textLost %}<<{{ textLost }}>>{% endif %}
                    
                    You lost all your {{ goldLost}} gold, and {{ experienceLost }} experience points. Try better next time.
                    TEXT,
                context: [
                    "badGuy" => $battleState->badGuy,
                    "goldLost" => $this->gold->getGold(null),
                    "experienceLost" => $experienceLost,
                    "textDefeated" => $battleState->badGuy->kwargs["textDefeated"] ?? null,
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            ),
        ];

        $this->logger->debug("Character {$event->character->id} has been slain and lost {$this->gold->getGold(null)}.");
        $this->gold->setGold(null, 0);
        $this->stats->addExperience(-$experienceLost);
    }
}