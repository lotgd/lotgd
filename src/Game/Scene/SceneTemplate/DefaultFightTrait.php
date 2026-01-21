<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Battle\BattleStateStatusEnum;
use LotGD2\Game\Battle\BattleTurn;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;

trait DefaultFightTrait
{
    private readonly DiceBagInterface $diceBag;
    private readonly Gold $gold;
    private readonly Stats $stats;

    public function fightAction(Stage $stage, Action $action, Scene $scene): void {
        $how = $action->getParameter("how");
        $battleState = $action->getParameter("battleState");

        if (!$battleState instanceof BattleState) {
            $this->onBattleStateDisappeared($stage, $action, $scene);
            $this->logger->critical("The BattleState was not transferred correctly", $action->getParameters());
            return;
        }

        // Find attachment
        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        if ($attachment) {
            $stage->setDescription(<<<TEXT
                You are in the middle of the fight against <.{{ badGuy.name }}.>.
                TEXT);

            $stage->addContext("badGuy", $battleState->badGuy);

            if ($action->getParameter("surprise", false) === true) {
                $battleTurn = BattleTurn::DamageTurnGoodGuy;
            } else {
                $battleTurn = BattleTurn::DamageTurnBoth;
            }

            if ($how === "flee") {
                $success = $this->onFightFlee($stage, $action, $scene, $battleTurn);

                if ($success) {
                    return;
                }

                $battleTurn = BattleTurn::DamageTurnBadGuy;
            }

            $stage->addAttachment($attachment, data: [
                "battleState" => $battleState,
            ]);

            $this->battle->fightOneRound($battleState, $battleTurn);

            if ($battleState->isOver()) {
                $this->onFightEnds($stage, $action, $scene, $battleState);
            } else {
                // Only add fight actions if the fight is not over
                $stage->clearActionGroups();
                $this->battle->addFightActions($stage, $scene, $battleState, ["op" => "fight"]);
            }
        } else {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
        }
    }

    /**
     * Redefine what happens if the battle state disappears
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @return void
     */
    public function onBattleStateDisappeared(Stage $stage, Action $action, Scene $scene): void
    {
        $this->addDefaultActions($stage, $action, $scene);
        $stage->setDescription("The battle suddenly ended.");
    }

    /**
     * Whenever a fight ends (expected or unexpected), the state can be reused to search for the the next fight. This
     * method offers a hook to add additional default actions (besides the usual scene connections).
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @return void
     */
    public function addDefaultActions(Stage $stage, Action $action, Scene $scene): void
    {

    }

    /**
     * Method that decides what happens when a character flees. By changing the return value, the battle turn type can
     * be overwritten from the previous value.
     *
     * By default, a failed flee action will result in a battle turn where only the enemy attacks.
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @param int $battleTurn
     * @return int
     */
    public function onFightFlee(Stage $stage, Action $action, Scene $scene, int $battleTurn): bool
    {
        if ($action->getParameter("surprise", false) === true || $this->diceBag->chance(0.3333, precision: 4)) {
            $this->logger->critical("Successfully escaped from the enemy.");

            $stage->setDescription(<<<TEXT
                    You have successfully fled your opponent!
                    
                TEXT . $scene->getDescription());

            // Add standard navigation
            $this->addDefaultActions($stage, $action, $scene);

            return true;
        } else {
            // Fleeing failed - meaning only the enemy gets to attack

            $stage->setDescription(<<<TEXT
                    You failed to flee your opponent! You are too busy trying to run away like a cowardly dog to try to fight.
                TEXT);

            return false;
        }
    }

    /**
     * Gets called whenever a fight is over.
     *
     * If no special code is necessary, decisions can also be shifted to onFightWon and onFightList to only change
     * what happens in these specific cases.
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @param BattleState $battleState
     * @return void
     */
    public function onFightEnds(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        $stage->addContext("textDefeated", $battleState->badGuy->kwargs["textDefeated"] ?? null);
        $stage->addContext("textLost", $battleState->badGuy->kwargs["textLost"] ?? null);

        if ($battleState->result === BattleStateStatusEnum::GoodGuyWon) {
            $this->onFightWon($stage, $action, $scene, $battleState);
        } else {
            $this->onFightLost($stage, $action, $scene, $battleState);
        }

        // Add standard navigation if battle is over
        $this->addDefaultActions($stage, $action, $scene);
    }

    /**
     * Handles the event triggered when a fight is won by the character.
     *
     *
     * @param Stage $stage
     * @param Action $action
     * @param Scene $scene
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        // Calculate how much gold to drop
        $gold = $this->diceBag->pseudoBell(0, $battleState->badGuy->kwargs["gold"] ?? 1);
        $stage->addContext("gold", $gold);
        $this->gold->addGold($gold);

        // Calculate how much experience to earn
        $experience = $battleState->badGuy->kwargs["experience"] ?? 1;
        // Add a bit of variation
        $expFlux = (int)round($experience / 10);
        $experience += (int)round($this->diceBag->bell(-$expFlux, $experience));
        // Add level difference bonus
        $expBonus = max(0, (int)round($experience * (0.25 * ($battleState->badGuy->level - $battleState->goodGuy->level)), 0));
        $experience += $expBonus;
        $stage->addContext("experience", $experience);
        $stage->addContext("bonusExperience", $expBonus);
        $this->stats->addExperience($experience);

        $stage->setDescription(<<<TEXT
            You have slain <.{{ badGuy.name }}.>. {% if textDefeated %}<<{{ textDefeated }}>>{% endif %}
            
            You earn {{ gold }} gold.
            
            {% if bonusExperience < 0 %}
                Due to how easy this fight was, you earn {{ bonusExperience|abs }} less. In total, you earn {{ experience }} experience points!
            {% elseif bonusExperience > 0 %}
                Due to how difficult this fight was, you earn additional {{ bonusExperience }}. In total, you earn {{ experience }} experience points!
            {% else %}
                You earn {{ experience }} experience points!
            {% endif %}
            TEXT
        );
    }

    /**
     * Handles the event triggered when a fight is lost by the character.
     *
     * Updates the context with information about the gold and experience lost during the fight.
     * Sets the stage description to reflect that the fight has been lost, detailing the penalties incurred.
     * Logs the event and adjusts the gold and experience of the character to represent the loss.
     *
     * @param Stage $stage The current stage of the game where the fight occurs.
     * @param Action $action The action involved in the fight.
     * @param Scene $scene The scene in which the fight takes place.
     * @param BattleState $battleState The state of the battle at the time of the loss.
     *
     * @return void
     */
    public function onFightLost(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        $stage->addContext("goldLost", $this->gold->getGold());
        $stage->addContext("experienceLost", round(0.1 * $this->stats->getExperience()));

        $stage->setDescription(<<<TEXT
            You have been slain by <.{{ badGuy.name }}.>. {% if textLost %}<<{{ textLost }}>>{% endif %}
            
            You lost all your {{ goldLost}} gold, and {{ experienceLost }} experience points. Try better next time.
            TEXT
        );

        $this->logger->debug("Character {$stage->getOwner()->getId()} has been slain and lost {$this->gold->getGold()}.");
        $this->gold->setGold(0);
        $this->stats->setExperience((int)round(0.9 * $this->stats->getExperience()));
    }
}