<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use Exception;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Race as RaceEntity;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Race\RaceInterface;
use LotGD2\Kernel;
use LotGD2\Repository\RaceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Stopwatch\Stopwatch;
use UnexpectedValueException;

/**
 * @phpstan-type  RacePropertyShape array{
 *     id: int,
 *     name: string,
 *     className: class-string,
 *     configuration: array<string, mixed>,
 * }
 */
class Race
{
    private ?ContainerInterface $container;

    const string RaceProperty = "race";

    public function __construct(
        private readonly ?Kernel $kernel,
        private readonly ?LoggerInterface $logger,
        private readonly ?Stopwatch $stopWatch,
        private readonly RaceRepository $raceRepository,
    ) {
        $this->container = $this->kernel?->getContainer();
    }

    #[AsEventListener(event: NewDay::PreNewDay, priority: 90)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $this->stopWatch->start("lotgd2.Race.onNewDay", "lotgd");

        $character = $event->character;
        $stop = false;

        if (isset($event->action->parameters["race"])) {
            $stop = $this->doRaceSelection($event);
        }

        if ($this->hasRace($character) === false) {
            $stop = $this->showRaceSelection($event);
        }

        if ($stop) {
            $event->setStopRender();
        }

        $this->stopWatch->stop("lotgd2.Race.onNewDay");
    }

    /**
     * Selects the race and sets the character's property to it.
     *
     * If the race 'disappears' between selections, it will return false and the race selection screen is displayed
     *  again.
     * @param StageChangeEvent $event
     * @return bool
     */
    public function doRaceSelection(StageChangeEvent $event): bool
    {
        $raceId = $event->action->parameters["race"];
        $race = $this->raceRepository->find($raceId);

        if ($race instanceof RaceEntity === false) {
            // Race disappeared, or the selection was faulty, let continue to the character screen
            return false;
        }

        $event->stage->title = "Where to you recall growing up?";
        $event->stage->clearParagraphs();
        $event->stage->addParagraph(new Paragraph(
            id: "lotdg2.paragraph.Race.onRaceSelection",
            text: $race->selectionText,
        ));

        $this->setRace($event->character, $race);

        return true;
    }

    /**
     * Adds all races with a short description to the stage's description and adds for each one an action to let the
     *  character choose.
     *
     * If there are no races available, the event will be skipped.
     * @param StageChangeEvent $event
     * @return bool
     */
    public function showRaceSelection(StageChangeEvent $event): bool
    {
        $races = $this->raceRepository->findAll();

        if (count($races) === 0) {
            // If there is no race to choose from, continue and skip this event
            $this->logger->info("There are no races available. Skipping race selection.");
            return false;
        }

        $event->stage->clear();
        $event->stage->title = "Where to you recall growing up?";
        $event->stage->addParagraph(new Paragraph(
            id: "lotdg2.paragraph.Race.generic",
            text: "From where are you?")
        );

        $actionGroup = new ActionGroup(
            id: "lotgd2.actionGroup.Race",
            title: "Pick one",
        );

        $event->stage->addActionGroup($actionGroup);

        foreach ($races as $race) {
            $event->stage->addParagraph(new Paragraph(
                id: "lotgd2.paragraph.Race.entry{$race->id}",
                text: $race->description
            ));

            $event->addAction($actionGroup, new Action(
                title: $race->name,
                parameters: [
                    "race" => $race->id,
                ],
                reference: "lotgd2.action.Race.entry{$race->id}"
            ));
        }

        return true;
    }

    /**
     * @param Character $character
     * @return null|RacePropertyShape
     */
    public function getRaceProperty(Character $character): null|array
    {
        return $character->getProperty("race");
    }

    /**
     * @param Character $character
     * @return bool
     */
    public function hasRace(Character $character): bool
    {
        return $this->getRaceProperty($character) !== null;
    }

    /**
     * @param Character $character
     * @return RaceEntity<array<string, mixed>>|null
     */
    public function getRace(Character $character): ?RaceEntity
    {
        $race = $this->getRaceProperty($character);
        return $race === null ? null : $this->raceRepository->find($race["id"]);
    }

    /**
     * @param Character $character
     * @return string|null
     */
    public function getRaceName(Character $character): ?string
    {
        $race = $this->getRaceProperty($character);
        return $race === null ? null : $race["name"];
    }

    /**
     * @param Character $character
     * @return string|null
     */
    public function getRaceClass(Character $character): ?string
    {
        $race = $this->getRaceProperty($character);
        return $race === null ? null : $race["className"];
    }

    /**
     * @param Character $character
     * @return null|array<string, mixed>
     */
    public function getRaceConfiguration(Character $character): ?array
    {
        $race = $this->getRaceProperty($character);
        return $race === null ? null : $race["configuration"];
    }

    /**
     * @param Character $character
     * @param RaceEntity<array<string, mixed>> $race
     * @return void
     */
    public function setRace(Character $character, RaceEntity $race): void
    {
        $character->setProperty("race", [
            "id" => $race->id,
            "name" => $race->name,
            "className" => $race->className,
            "configuration" => $race->configuration,
        ]);

        if ($race->className === null) {
            // No class - no hook.
            return;
        }

        if (!class_exists($race->className)) {
            // If the class does not exist, we log this as a critical error.
            $this->logger->critical("The race class {$race->className} does not exist.");
            return;
        }

        try {
            $raceClass = $this->container->get($race->className);
            $raceClass->onSelect($character, $race);
        } catch (Exception $e) {
            // If the class does not exist, we log this as a critical error.
            $this->logger->critical("There was an issue with the race class {$race->className}. {$e->getMessage()}", context: ["exception" => $e]);
        }
    }
}