<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\HealerTemplateType;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\WealthHandler;
use LotGD2\Game\Handler\HealthHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @phpstan-type HealerTemplateConfiguration array{
 *      stealHealth: bool,
 *      text: array{
 *          onEntryAndHealthy: string,
 *          onEntryAndDamaged: string,
 *          onEntryAndOverhealed: string,
 *          onHealEnoughGold: string,
 *          onHealNotEnoughGold: string,
 *      },
 *      actionGroupPotionTitle: string,
 *      actionCompleteHealingTitle: string,
 *  }
 * @implements SceneTemplateInterface<HealerTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(HealerTemplateType::class)]
class HealerTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const ActionGroupPotions = "lotgd2.actionGroup.healerTemplate.potions";
    const ActionCompleteHealing = "lotgd2.action.healerTemplate.complete";
    const ActionPartialHealing = "lotgd2.action.healerTemplate.partial";

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Security        $security,
        private readonly HandlerRegistry $handlerRegistry,
    ) {

    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameter("op") ?? null;

        if ($op === "cheat") {
            $what = $this->action->getParameter("what") ?? null;

            if ($what === "heal") {
                $this->handlerRegistry->get(HealthHandler::class)->heal($this->character);
            }
        }

        match($op) {
            default => $this->defaultAction(),
            "heal" => $this->healAction(),
        };
    }

    public function defaultAction(): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");
        $healthHandler = $this->handlerRegistry->get(HealthHandler::class);

        if ($healthHandler->isAlive($this->character) === false) {
            $this->stage->paragraphs = [
                new Paragraph(
                    "lotgd2.paragraph.healerTemplate.isDeadMessage",
                    "You are dead and cannot get healing from the Healer's hut. Try waiting for a new day to continue playing."
                ),
            ];
        } elseif ($healthHandler->getHealth($this->character) < $healthHandler->getMaxHealth($this->character)) {
            $this->stage->addParagraph(new Paragraph(
                id: "lotgd2.paragraph.healerTemplate.onEntryAndDamaged",
                text: $this->scene->templateConfig["text"]["onEntryAndDamaged"],
                context: ["price" => $this->getPrice($this->character)],
            ));

            $this->addPotionActions();
        } elseif ($healthHandler->getHealth($this->character) > $healthHandler->getMaxHealth($this->character) && ($this->scene->templateConfig["stealHealth"] ?? true) === true) {
            $this->stage->addParagraph(new Paragraph(
                id: "lotgd2.paragraph.healerTemplate.onEntryAndOverhealed",
                text: $this->scene->templateConfig["text"]["onEntryAndOverhealed"],
            ));
        } else {
            $this->stage->addParagraph(new Paragraph(
                id: "lotgd2.paragraph.healerTemplate.onEntryAndHealthy",
                text: $this->scene->templateConfig["text"]["onEntryAndHealthy"],
            ));
        }

        if ($this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $cheatsGroup = new ActionGroup("lotgd2.actionGroup.healerTemplate.cheats", "Cheats");
            $cheatsGroup->setActions([
                new Action(
                    scene: $this->scene,
                    title: "#! Complete Heal",
                    parameters: ["op" => "cheat", "what" => "heal"],
                    reference: "lotgd2.action.healerTemplate.cheats.experience",
                ),
            ]);
            $this->stage->addActionGroup($cheatsGroup);
        }
    }

    public function healAction(): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");

        $healthHandler = $this->handlerRegistry->get(HealthHandler::class);
        $wealthHandler = $this->handlerRegistry->get(WealthHandler::class);

        $amount = (int)($this->action->getParameter("amount") ?? 0);
        $price = (int)($this->action->getParameter("price") ?? 0);

        if ($price === 0 or $wealthHandler->getGold($this->character) >= $price) {
            $this->logger->debug("{$this->character->id}: Healed by $amount for $price gold.");

            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.healerTemplate.onHealEnoughGold",
                    text: $this->scene->templateConfig["text"]["onHealEnoughGold"],
                    context: [
                        "price" => $price,
                        "amount" => $amount,
                    ],
                )
            ];

            $healthHandler->heal($this->character, $amount);
            $wealthHandler->addGold($this->character, -$price);
        } else {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.healerTemplate.onHealNotEnoughGold",
                    text: $this->scene->templateConfig["text"]["onHealNotEnoughGold"],
                    context: [
                        "price" => $price,
                    ],
                )
            ];
        }
    }

    public function getPrice(Character $character): int
    {
        $healthHandler = $this->handlerRegistry->get(HealthHandler::class);
        $level = max(1, $character->level);
        $price = log($level) * ($healthHandler->getMaxHealth($character) - $healthHandler->getHealth($character) + 10);

        return (int)round($price);
    }

    public function addPotionActions(): void
    {
        $healthHandler = $this->handlerRegistry->get(HealthHandler::class);
        $actionGroup = new ActionGroup(
            id: self::ActionGroupPotions,
            title: $this->scene->templateConfig["actionGroupPotionTitle"],
        );

        $healthDelta = $healthHandler->getMaxHealth($this->character) - $healthHandler->getHealth($this->character);
        $price = $this->getPrice($this->stage->owner);

        $actionGroup->addAction(new Action(
            scene: $this->scene,
            title: $this->scene->templateConfig["actionCompleteHealingTitle"],
            parameters: ["op" => "heal", "amount" => $healthDelta, "price" => $price],
            reference: self::ActionCompleteHealing,
        ));

        $healOptionList = [$healthDelta => $price];

        // Only add partial healing options if healing is not free
        // Healing is usually free on level 1
        if ($price > 0) {
            for ($i = 90; $i > 0; $i -= 10) {
                $partialPrice = (int)ceil($price * $i / 100);
                $partialHeal = (int)round(max(1, $healthDelta * $i / 100));

                if (isset($healOptionList[$partialHeal])) {
                    continue;
                }

                $actionGroup->addAction(new Action(
                    scene: $this->scene,
                    title: "Heal {$partialHeal} points for {$partialPrice} gold",
                    parameters: ["op" => "heal", "amount" => $partialHeal, "price" => $partialPrice],
                    reference: self::ActionPartialHealing . ".$i",
                ));

                $healOptionList[$partialHeal] = $partialPrice;
            }
        }

        $this->stage->addActionGroup($actionGroup);
    }
}