<?php
declare(strict_types=1);

namespace LotGD2\Game\Race;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Character\LootBag;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Race;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\LootBagEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\Race\StandardRaceType;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\RaceHandler as RaceHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @phpstan-type StandardRaceConfiguration array{
 *     attack?: int,
 *     defense?: int,
 *     turns?: int,
 *     health?: int,
 *     goldFactor?: float,
 *     experienceFactor?: float,
 * }
 * @implements RaceInterface<StandardRaceConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(StandardRaceType::class)]
class StandardRace implements RaceInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private StatsHandler $stats,
        private HealthHandler $health,
        private RaceHandler $race,
    ) {
    }

    public function onSelect(Character $character, Race $race): void
    {
        $loggerContext = ["what" => "select", "race" => $race];

        if (isset($race->configuration["attack"]) and $race->configuration["attack"] <> 0) {
            $value = $race->configuration["attack"];
            $this->logger->debug("Character $character->id: Increase attack by $value for race {$race->name}.", $loggerContext);
            $this->stats->addAttack($value, $character);
        }

        if (isset($race->configuration["defense"]) and $race->configuration["defense"] <> 0) {
            $value = $race->configuration["defense"];
            $this->logger->debug("Character $character->id: Increase defense by $value for race {$race->name}.", $loggerContext);
            $this->stats->addDefense($value, $character);
        }

        if (isset($race->configuration["health"]) && $race->configuration["health"] <> 0) {
            $value = $race->configuration["health"];
            $this->logger->debug("Character $character->id: Increase health by $value for race {$race->name}.", $loggerContext);
            $this->health->addMaxHealth($value, $character);
        }
    }

    public function onDeselect(Character $character, Race $race, array $oldConfiguration): void
    {
        $loggerContext = ["what" => "unselect", "race" => $race, "oldConfiguration" => $oldConfiguration];

        if (isset($oldConfiguration["attack"]) && $oldConfiguration["attack"] <> 0) {
            $value = $oldConfiguration["attack"];
            $this->logger->debug("Character $character->id: Decrease attack by $value for race {$race->name}.", $loggerContext);
            $this->stats->addAttack(-$value, $character);
        }

        if (isset($oldConfiguration["defense"]) && $oldConfiguration["defense"] <> 0) {
            $value = $oldConfiguration["defense"];
            $this->logger->debug("Character $character->id: Decrease defense by $value for race {$race->name}.", $loggerContext);
            $this->stats->addDefense(-$value, $character);
        }

        if (isset($oldConfiguration["health"]) && $oldConfiguration["health"] <> 0) {
            $value = $oldConfiguration["health"];
            $this->logger->debug("Character $character->id: Increase health by $value for race {$race->name}.", $loggerContext);
            $this->health->addMaxHealth(-$value, $character);
        }
    }

    #[AsEventListener(event: NewDay::OnNewDayAfter)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $character = $event->character;
        if ($this->race->hasRace($character) and $this->race->getRaceClass($character) === self::class) {
            $race = $this->race->getRace($character);

            if ($race === null) {
                $this->logger->critical("Race disappeared");
                return;
            }

            $value = $race->configuration["turns"] ?? 0;
            $loggerContext = ["what" => "turn", "race" => $race];

            if ($value === 0) {
                return;
            }

            $this->health->addTurns($value, $character);
            $this->logger->debug("Character $character->id: Increase turns by $value for race {$race->name}.", $loggerContext);
            $event->stage->addParagraph(new Paragraph(
                "lotgd2.paragraph.StandardRace.onNewDay",
                text: <<<TXT
                    Because of your race, everything is easier for you.
                    You get {{ value }} extra {% if value == 1 %}turn{%else%}turns{%endif%}
                    TXT,
                context: ["value" => $value],
            ));
        }
    }



    #[AsEventListener(FightTemplate::OnLootBagFill, priority: -10)]
    public function onLootBagFill(LootBagEvent $event): void
    {
        $raceClass = $this->race->getRaceClass($event->character);

        if ($raceClass !== self::class) {
            return;
        }

        $raceConfiguration = $this->race->getRaceConfiguration($event->character);

        if ($raceConfiguration === null) {
            return;
        }

        $this->changeGoldLoot($raceConfiguration, $event->lootBag);
        $this->changeExperienceReward($raceConfiguration, $event->lootBag);
    }

    /**
     * @param StandardRaceConfiguration $raceConfiguration
     * @param LootBag $lootBag
     * @return void
     */
    public function changeGoldLoot(array $raceConfiguration, LootBag $lootBag): void
    {
        if (!isset($raceConfiguration["goldFactor"])) {
            return;
        }

        $lootPosition = $lootBag->get(GoldHandler::GoldLoot);

        if ($lootPosition === null) {
            $this->logger->info("The LootBag does not have a gold position. Cannot change gold reward.");
            return;
        }

        $lootConfig = $lootPosition->loot;
        $lootConfig["maxValue"] = (int)ceil($lootConfig["maxValue"] * $raceConfiguration["goldFactor"]);

        // Required to keep track if we modified this position
        $lootConfig["mod.StandardRace"] = $raceConfiguration["goldFactor"];

        // Set loot back
        $lootPosition->loot = $lootConfig;

        $this->logger->debug("Modified gold reward by factor {$raceConfiguration['goldFactor']}.");
    }

    /**
     * @param StandardRaceConfiguration $raceConfiguration
     * @param LootBag $lootBag
     * @return void
     */
    public function changeExperienceReward(array $raceConfiguration, LootBag $lootBag): void
    {
        if (!isset($raceConfiguration["experienceFactor"])) {
            return;
        }

        $lootPosition = $lootBag->get(StatsHandler::ExperienceLoot);

        if ($lootPosition === null) {
            $this->logger->info("The LootBag does not have an experience position. Cannot change experience reward.");
            return;
        }

        $lootConfig = $lootPosition->loot;
        $lootConfig["experience"] = (int)ceil($lootConfig["experience"] * $raceConfiguration["experienceFactor"]);

        // Required to keep track if we modified this position
        $lootConfig["mod.StandardRace"] = $raceConfiguration["experienceFactor"];

        // Set loot back
        $lootPosition->loot = $lootConfig;

        $this->logger->debug("Modified experience reward by factor {$raceConfiguration['goldFactor']}.");
    }
}