<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Form\Scene\SceneTemplate\HealerTemplateType;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
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
readonly class HealerTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const ActionGroupPotions = "lotgd2.actionGroup.healerTemplate.potions";
    const ActionCompleteHealing = "lotgd2.action.healerTemplate.complete";
    const ActionPartialHealing = "lotgd2.action.healerTemplate.partial";

    public function __construct(
        private LoggerInterface $logger,
        private Security $security,
        private Health $health,
        private Gold $gold,
    ) {

    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameter("op") ?? null;

        if ($op === "cheat") {
            $what = $action->getParameter("what") ?? null;

            if ($what === "heal") {
                $this->health->heal();
            }
        }

        match($op) {
            default => $this->defaultAction($stage, $action, $scene),
            "heal" => $this->healAction($stage, $action, $scene),
        };
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");

        if ($this->health->isAlive() === false) {
            $stage->addDescription("You are dead and cannot get healing from the Healer's hut. Try waiting for a new day to continue playing.");
        } elseif ($this->health->getHealth() < $this->health->getMaxHealth()) {
            $stage->addDescription($scene->templateConfig["text"]["onEntryAndDamaged"]);
            $stage->addContext("price", $this->getPrice($stage->owner));
            $this->addPotionActions($stage, $scene);
        } elseif ($this->health->getHealth() > $this->health->getMaxHealth() && ($scene->templateConfig["stealHealth"] ?? true) === true) {
            $stage->addDescription($scene->templateConfig["text"]["onEntryAndOverhealed"]);
        } else {
            $stage->addDescription($scene->templateConfig["text"]["onEntryAndHealthy"]);
        }

        if ($this->security->isGranted("ROLE_CHEATS_ENABLED")) {
            $cheatsGroup = new ActionGroup("lotgd2.actionGroup.healerTemplate.cheats", "Cheats");
            $cheatsGroup->setActions([
                new Action(
                    scene: $scene,
                    title: "#! Complete Heal",
                    parameters: ["op" => "cheat", "what" => "heal"],
                    reference: "lotgd2.action.healerTemplate.cheats.experience",
                ),
            ]);
            $stage->addActionGroup($cheatsGroup);
        }
    }

    public function healAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");

        $character = $stage->owner;
        $amount = $action->getParameter("amount") ?? 0;
        $price = $action->getParameter("price") ?? 0;

        if ($price === 0 or $this->gold->getGold() >= $price) {
            $this->logger->debug("{$character->id}: Healed by $amount for $price gold.");

            $stage->description = $scene->templateConfig["text"]["onHealEnoughGold"];
            $stage->addContext("price", $price);
            $stage->addContext("amount", $amount);

            $this->health->heal($amount);
            $this->gold->addGold(-$price);
        } else {
            $stage->description = $scene->templateConfig["text"]["onHealNotEnoughGold"];
            $stage->addContext("price", $price);
        }
    }

    public function getPrice(Character $character): int
    {
        $level = max(1, $character->level);
        $price = log($level) * ($this->health->getMaxHealth() - $this->health->getHealth() + 10);

        return (int)round($price);
    }

    public function addPotionActions(Stage $stage, Scene $scene): void
    {
        $actionGroup = new ActionGroup(
            id: self::ActionGroupPotions,
            title: $scene->templateConfig["actionGroupPotionTitle"],
        );

        $healthDelta = $this->health->getMaxHealth() - $this->health->getHealth();
        $price = $this->getPrice($stage->owner);

        $actionGroup->addAction(new Action(
            scene: $scene,
            title: $scene->templateConfig["actionCompleteHealingTitle"],
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
                    scene: $scene,
                    title: "Heal {$partialHeal} points for {$partialPrice} gold",
                    parameters: ["op" => "heal", "amount" => $partialHeal, "price" => $partialPrice],
                    reference: self::ActionPartialHealing . ".$i",
                ));

                $healOptionList[$partialHeal] = $partialPrice;
            }
        }

        $stage->addActionGroup($actionGroup);
    }
}