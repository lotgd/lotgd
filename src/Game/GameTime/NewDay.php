<?php
declare(strict_types=1);

namespace LotGD2\Game\GameTime;

use DateTime;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NewDay
{
    const string LastNewDayProperty = "lotgd2.internal.lastNewDay";
    const string PreNewDay = "lotgd2.event.preNewDay";
    const string PostNewDay = "lotgd2.event.postNewDay";

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ActionService $actionService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function isNewDay(Character $character): bool
    {
        $lastNewDay = $character->getProperty(self::LastNewDayProperty);

        if ($lastNewDay === null) {
            return true;
        } else {
            $lastNewDay = (int)$lastNewDay;

            $interval = new DateTime()->getTimestamp() - $lastNewDay;

            if ($interval >= 1800) {
                return true;
            }
            return false;
        }
    }

    public function setLastNewDay(Character $character): void
    {
        $thisNewDay = new DateTime()->getTimestamp();
        $this->logger->debug("{$character->id}: Set last new day to {$thisNewDay}.");

        $character->setProperty(self::LastNewDayProperty, $thisNewDay);
    }

    public function resetNewDay(Character $character): void
    {
        $this->logger->debug("{$character->id}: Resets last new day.");
        $character->setProperty(self::LastNewDayProperty, null);
    }

    public function render(Stage $stage, Action $action, Scene $scene): void
    {
        $character = $stage->owner;

        $event = new StageChangeEvent($stage, $action, $scene);
        $this->eventDispatcher->dispatch($event, self::PreNewDay);

        if ($event->stopRender) {
            return;
        }

        $this->logger->debug("{$character->id}: It is a new day.");

        $stage->scene = null;
        $stage->title = "It is a new day!";
        $stage->description = "It is a new day!";
        $stage->clearContext();
        $stage->clearAttachments();
        $this->actionService->resetActionGroups($stage);

        // Connect to the originally targeted Scene
        $stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $scene,
            title: "Continue",
            parameters: $action->parameters,
            reference: $action->reference,
        ));

        // Set last new day to the current day.
        $this->setLastNewDay($character);

        $event = new StageChangeEvent($stage, $action, $scene);
        $this->eventDispatcher->dispatch($event, self::PostNewDay);
    }
}