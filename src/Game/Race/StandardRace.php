<?php
declare(strict_types=1);

namespace LotGD2\Game\Race;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Race;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\Race\StandardRaceType;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Race as RaceHandler;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\GameTime\NewDay;
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
        private Stats $stats,
        private Health $health,
        private RaceHandler $race,
    ) {
    }

    public function onSelect(Character $character, Race $race): void
    {
        $loggerContext = ["what" => "select", "race" => $race];

        if ($race->configuration["attack"] <> 0) {
            $value = $race->configuration["attack"];
            $this->logger->debug("Character $character->id: Increase attack by $value for race {$race->name}.", $loggerContext);
            $this->stats->addAttack($value, $character);
        }

        if ($race->configuration["defense"] <> 0) {
            $value = $race->configuration["defense"];
            $this->logger->debug("Character $character->id: Increase defense by $value for race {$race->name}.", $loggerContext);
            $this->stats->addDefense($value, $character);
        }

        if ($race->configuration["health"] <> 0) {
            $value = $race->configuration["health"];
            $this->logger->debug("Character $character->id: Increase health by $value for race {$race->name}.", $loggerContext);
            $this->health->addMaxHealth($value, $character);
        }
    }

    public function onDeselect(Character $character, Race $race, array $oldConfiguration): void
    {
        $loggerContext = ["what" => "unselect", "race" => $race, "oldConfiguration" => $oldConfiguration];

        if ($oldConfiguration["attack"] <> 0) {
            $value = $race->configuration["attack"];
            $this->logger->debug("Character $character->id: Decrease attack by $value for race {$race->name}.", $loggerContext);
            $this->stats->addAttack(-$value, $character);
        }

        if ($race->configuration["defense"] <> 0) {
            $value = $race->configuration["defense"];
            $this->logger->debug("Character $character->id: Decrease defense by $value for race {$race->name}.", $loggerContext);
            $this->stats->addDefense(-$value, $character);
        }

        if ($race->configuration["health"] <> 0) {
            $value = $race->configuration["health"];
            $this->logger->debug("Character $character->id: Increase health by $value for race {$race->name}.", $loggerContext);
            $this->health->addMaxHealth(-$value, $character);
        }
    }

    #[AsEventListener(event: NewDay::PostNewDay)]
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
            $this->logger->debug("Character $character->id: Increase health by $value for race {$race->name}.", $loggerContext);
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
}