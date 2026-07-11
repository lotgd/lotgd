<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate\Special;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\Special\StumbleSpecialTemplateType;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneTemplate\SpecialTemplate;
use LotGD2\Game\Scene\SpecialService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @phpstan-type StumbleSpecialConfiguration array{
 *     damageChance: float,
 *     minDamage: string,
 *     maxDamage: string,
 *     playerCanDie: bool,
 * }
 * @extends SpecialTemplate<StumbleSpecialConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(StumbleSpecialTemplateType::class)]
class StumbleSpecialTemplate extends SpecialTemplate
{
    public function __construct(
        private readonly DiceBagInterface $diceBag,
        private readonly SpecialService $specialService,
        private readonly ExpressionService $expressionService,
        private readonly HealthHandler $healthHandler,
    ) {
    }

    public function onSceneChange(): void
    {
        $playerCanDie = $this->scene->templateConfig["playerCanDie"] ?? true;
        $minDamage = $this->scene->templateConfig["minDamage"] ?? "character.level";
        $maxDamage = $this->scene->templateConfig["maxDamage"] ?? "character.level*3";
        $damageChance = ($this->scene->templateConfig["damageChance"] ?? 100) / 100.;

        if ($this->diceBag->chance($damageChance)) {
            $this->stage->paragraphs[Stage::SceneText]?->addContext("somethingHappened", true);

            // Calculate damage
            $minDamage = $this->expressionService->evaluateInteger($this->character, $minDamage);
            $maxDamage = $this->expressionService->evaluateInteger($this->character, $maxDamage);

            $damage = $this->diceBag->pseudoBell($minDamage, $maxDamage);

            // Check if player cannot die and adjust damage accordingly
            if ($playerCanDie === false) {
                // If health is 3 and damage is 5, final damage will be 2
                // If health if 5 and damage is 3, final damage will be 3
                $damage = min($this->healthHandler->getHealth($this->character) - 1, $damage);
            }

            $this->healthHandler->heal(-$damage, $this->character);

            $this->stage->paragraphs[Stage::SceneText]?->addContext("damage", $damage);

            if (!$this->healthHandler->isAlive($this->character)) {
                $this->stage->addParagraph(new Paragraph(
                    id: "lotgd2.paragraph.Special.StumbleSpecial.died",
                    text: "You died!!!"
                ));

                $this->stage->addParagraph(new Paragraph(
                    id: "lotgd2.paragraph.Special.StumbleSpecial.deathMessage",
                    text: "Luckily, you did not loose any gold or experience.",
                ));
            }
        } else {
            $this->stage->paragraphs[Stage::SceneText]?->addContext("somethingHappened", false);
        }

        $this->specialService->addReturnAction($this->stage);
    }
}