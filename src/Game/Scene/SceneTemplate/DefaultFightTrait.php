<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
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

    public function fightAction(): void {
        $how = $this->action->getParameter("how");
        $battleState = $this->action->getParameter("battleState");

        if (!$battleState instanceof BattleState) {
            $this->onBattleStateDisappeared();
            $this->logger->critical("The BattleState was not transferred correctly", $this->action->getParameters());
            return;
        }

        // Find attachment
        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        if ($attachment) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.DefaultFightTrait.FightMessage",
                    text: "You are in the middle of the fight against <.{{ badGuy.name }}.>.",
                    context: [
                        "badGuy" => $battleState->badGuy,
                    ]
                )
            ];

            if ($this->action->getParameter("surprise", false) === true) {
                $battleTurn = BattleTurn::DamageTurnGoodGuy;
            } else {
                $battleTurn = BattleTurn::DamageTurnBoth;
            }

            if ($how === "flee") {
                $success = $this->onFightFlee($battleTurn);

                if ($success) {
                    return;
                }
            }

            $this->stage->addAttachment($attachment, data: [
                "battleState" => $battleState,
            ]);

            $rounds = $this->action->getParameter("rounds") ?? 1;

            do {
                $rounds -= 1;
                $this->battle->fightOneRound($battleState, $battleTurn);

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
                $this->onFightEnds($battleState);
            } else {
                // Only add fight actions if the fight is not over
                $this->stage->clearActionGroups();
                $this->battle->addFightActions($this->stage, $this->scene, $battleState, ["op" => "fight"]);
            }
        } else {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
        }
    }

    /**
     * Redefine what happens if the battle state disappears
     * @return void
     */
    public function onBattleStateDisappeared(): void
    {
        $this->addDefaultActions();
        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.DefaultFightTrait.SuddenFightEnd",
                text: "The battle suddenly ended.",
            )
        ];
    }

    /**
     * Whenever a fight ends (expected or unexpected), the state can be reused to search for the next fight. This
     * method offers a hook to add additional default actions (besides the usual scene connections).
     * @return void
     */
    public function addDefaultActions(): void
    {

    }

    /**
     * Method that decides what happens when a character flees. By changing the return value, the battle turn type can
     * be overwritten from the previous value.
     *
     * By default, a failed flee action will result in a battle turn where only the enemy attacks.
     * @param int $battleTurn
     * @return bool
     */
    public function onFightFlee(int &$battleTurn): bool
    {
        if ($this->action->getParameter("surprise", false) === true || $this->diceBag->chance(0.3333, precision: 4)) {
            $this->logger->critical("Successfully escaped from the enemy.");

            $this->stage->paragraphs = [
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
            $this->addDefaultActions();

            return true;
        } else {
            // Fleeing failed - meaning only the enemy gets to attack
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd.paragraph.DefaultFightTrait.onFlightFleeFailed",
                    text: "You failed to flee your opponent! You are too busy trying to run away like a cowardly dog to try to fight!",
                ),
            ];

            $battleTurn = BattleTurn::DamageTurnBadGuy;

            return false;
        }
    }

    /**
     * Gets called whenever a fight is over.
     *
     * If no special code is necessary, decisions can also be shifted to onFightWon and onFightList to only change
     * what happens in these specific cases.
     * @param BattleState $battleState
     * @return void
     */
    public function onFightEnds(BattleState $battleState): void
    {
        if ($battleState->result === BattleStateStatusEnum::GoodGuyWon) {
            $this->onFightWon($battleState);
        } else {
            $this->onFightLost($battleState);
        }

        // Add standard navigation if battle is over
        $this->addDefaultActions();
    }

    /**
     * Handles the event triggered when a fight is won by the character.
     *
     * @param BattleState $battleState
     * @return void
     */
    public function onFightWon(BattleState $battleState): void
    {
        // Calculate how much gold to drop
        $gold = $this->diceBag->pseudoBell(0, $battleState->badGuy->kwargs["gold"] ?? 1);
        $this->gold->addGold($gold);

        // Calculate how much experience to earn
        $experience = $battleState->badGuy->kwargs["experience"] ?? 1;
        // Add a bit of variation
        $expFlux = (int)round($experience / 10);
        $experience += (int)round($this->diceBag->bell(-$expFlux, $experience));
        // Add level difference bonus
        $expBonus = max(0, (int)round($experience * (0.25 * ($battleState->badGuy->level - $battleState->goodGuy->level)), 0));
        $experience += $expBonus;
        $this->stats->addExperience($experience);

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.DefaultFightTrait.onFightWon",
                text:<<<TEXT
                    You have slain <.{{ badGuy.name }}.>. {% if textDefeated %}<<{{ textDefeated }}>>{% endif %}
                    
                    You earn {{ gold }} gold.
                    
                    {% if bonusExperience < 0 %}
                        Due to how easy this fight was, you earn {{ bonusExperience|abs }} less. In total, you earn {{ experience }} experience points!
                    {% elseif bonusExperience > 0 %}
                        Due to how difficult this fight was, you earn additional {{ bonusExperience }}. In total, you earn {{ experience }} experience points!
                    {% else %}
                        You earn {{ experience }} experience points!
                    {% endif %}
                    TEXT,
                context: [
                    "badGuy" => $battleState->badGuy,
                    "textDefeated" => $battleState->badGuy->kwargs["textDefeated"] ?? null,
                    "bonusExperience" => $expBonus,
                    "experience" => $experience,
                    "gold" => $gold,
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            ),
        ];
    }

    /**
     * Handles the event triggered when a fight is lost by the character.
     *
     * Updates the context with information about the gold and experience lost during the fight.
     * Sets the stage description to reflect that the fight has been lost, detailing the penalties incurred.
     * Logs the event and adjusts the gold and experience of the character to represent the loss.
     *
     * @param BattleState $battleState The state of the battle at the time of the loss.
     *
     * @return void
     */
    public function onFightLost(BattleState $battleState): void
    {
        $experienceLost = (int)round(0.1 * $this->stats->getExperience());

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.DefaultFightTrait.onFightLost",
                text: <<<TEXT
                    You have been slain by <.{{ badGuy.name }}.>. {% if textLost %}<<{{ textLost }}>>{% endif %}
                    
                    You lost all your {{ goldLost}} gold, and {{ experienceLost }} experience points. Try better next time.
                    TEXT,
                context: [
                    "badGuy" => $battleState->badGuy,
                    "goldLost" => $this->gold->getGold(),
                    "experienceLost" => $experienceLost,
                    "textDefeated" => $battleState->badGuy->kwargs["textDefeated"] ?? null,
                    "textLost" => $battleState->badGuy->kwargs["textLost"] ?? null,
                ]
            ),
        ];

        $this->logger->debug("Character {$this->stage->owner->id} has been slain and lost {$this->gold->getGold()}.");
        $this->gold->setGold(0);
        $this->stats->addExperience(-$experienceLost);
    }
}