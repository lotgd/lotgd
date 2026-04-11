<?php
declare(strict_types=1);

namespace LotGD2\Game\GameTime;

use DateTime;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NewDay
{
    const string LastNewDayProperty = "lotgd2.internal.lastNewDay";
    const string OnNewDayBefore = "lotgd2.NewDay.event.before";
    const string OnNewDayAfter = "lotgd2.NewDay.event.after";

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

        // Reset before PreNewDay to have a "fresh" stage for listeners
        $this->logger->debug("{$character->id}: It is a new day.");

        $stage->scene = null;
        $stage->title = "It is a new day!";
        $stage->paragraphs = [
            new Paragraph(
                id: "lotgd.paragraph.NewDay.newDay",
                text: "It is a new day!",
            ),
        ];
        $stage->clearAttachments();
        $this->actionService->resetActionGroups($stage);

        $event = new StageChangeEvent($stage, $action, $scene);
        $this->eventDispatcher->dispatch($event, self::OnNewDayBefore);

        if ($event->stopRender) {
            $this->logger->debug("NewDay: Render was stopped by OnNewDayBefore event.");
            return;
        }

        // We do not set the current last day or add navigation if the eventDispatcher was stopped.

        // Connect to the originally targeted Scene
        $this->addContinueAction($stage, $scene, $action);

        // Set the last new day to the current day.
        $this->setLastNewDay($character);

        $event = new StageChangeEvent($stage, $action, $scene);
        $this->eventDispatcher->dispatch($event, self::OnNewDayAfter);
    }

    private function addContinueAction(Stage $stage, Scene $scene, Action $action): void
    {
        $stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $scene,
            title: "Continue",
            parameters: $action->parameters,
            reference: $action->reference,
        ));
    }
}