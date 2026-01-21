<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
readonly class HealerTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    const ActionGroupPotions = "lotgd2.actionGroup.healerTemplate.potions";
    const ActionCompleteHealing = "lotgd2.action.healerTemplate.complete";
    const ActionPartialHealing = "lotgd2.action.healerTemplate.partial";

    public function __construct(
        private LoggerInterface $logger,
        private Health $health,
        private Gold $gold,
    ) {

    }

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();
        $resolver->define("stealHealth")->allowedTypes("bool")->default(true);
        $resolver->define("actionGroupPotionTitle")->allowedTypes("string")->default("Potions");
        $resolver->define("actionCompleteHealingTitle")->allowedTypes("string")->default("Complete Healing");

        $resolver
            ->define("text")
            ->default(function (OptionsResolver $resolver) {
                $resolver
                    ->define("onEntryAndHealthy")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("onEntryAndDamaged")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("onEntryAndOverhealed")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("onHealEnoughGold")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("onHealNotEnoughGold")
                    ->required()
                    ->allowedTypes('string');
            });

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameter("op") ?? null;

        match($op) {
            default => $this->defaultAction($stage, $action, $scene),
            "heal" => $this->healAction($stage, $action, $scene),
        };
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");

        if ($this->health->getHealth() < $this->health->getMaxHealth()) {
            $stage->addDescription($scene->getTemplateConfig()["text"]["onEntryAndDamaged"]);
            $stage->addContext("price", $this->getPrice($stage->getOwner()));
            $this->addPotionActions($stage, $scene);
        } elseif ($this->health->getHealth() > $this->health->getMaxHealth() && ($scene->getTemplateConfig()["stealHealth"] ?? true) === true) {
            $stage->addDescription($scene->getTemplateConfig()["text"]["onEntryAndOverhealed"]);
        } else {
            $stage->addDescription($scene->getTemplateConfig()["text"]["onEntryAndHealthy"]);
        }
    }

    public function healAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called HealerTemplate::defaultAction");

        $character = $stage->getOwner();
        $amount = $action->getParameter("amount") ?? 0;
        $price = $action->getParameter("price") ?? 0;

        if ($price === 0 or $this->gold->getGold() >= $price) {
            $this->logger->debug("{$character->getId()}: Healed by $amount for $price gold.");

            $stage->setDescription($scene->getTemplateConfig()["text"]["onHealEnoughGold"]);
            $stage->addContext("price", $price);
            $stage->addContext("amount", $amount);

            $this->health->heal($amount);
            $this->gold->addGold(-$price);
        } else {
            $stage->setDescription($scene->getTemplateConfig()["text"]["onHealNotEnoughGold"]);
            $stage->addContext("price", $price);
        }
    }

    public function getPrice(Character $character): int
    {
        $level = max(1, $character->getLevel());
        $price = log($level) * ($this->health->getMaxHealth() - $this->health->getHealth() + 10);

        return (int)round($price);
    }

    public function addPotionActions(Stage $stage, Scene $scene): void
    {
        $actionGroup = new ActionGroup(
            id: self::ActionGroupPotions,
            title: $scene->getTemplateConfig()["actionGroupPotionTitle"],
        );

        $healthDelta = $this->health->getMaxHealth() - $this->health->getHealth();
        $price = $this->getPrice($stage->getOwner());

        $actionGroup->addAction(new Action(
            scene: $scene,
            title: $scene->getTemplateConfig()["actionCompleteHealingTitle"],
            parameters: ["op" => "heal", "amount" => $healthDelta, "price" => $price],
            reference: self::ActionCompleteHealing,
        ));

        // Only add partial healing options if healing is not free
        // Healing is usually free on level 1
        if ($price > 0) {
            for ($i = 90; $i > 0; $i -= 10) {
                $partialPrice = (int)ceil($price * $i / 100);
                $partialHeal = (int)round(max(1, $healthDelta * $i / 100));

                $actionGroup->addAction(new Action(
                    scene: $scene,
                    title: "{}",
                    parameters: ["op" => "heal", "amount" => $partialHeal, "price" => $partialPrice],
                    reference: self::ActionPartialHealing,
                ));
            }
        }

        $stage->addActionGroup($actionGroup);
    }
}