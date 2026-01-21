<?php
declare(strict_types=1);

namespace LotGD2\Game\GameTime;

use DateTime;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;

class NewDay
{
    const LastNewDayProperty = "lotgd2.internal.lastNewDay";

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ActionService $actionService,
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
            dump($interval, $lastNewDay);

            if ($interval > 60) {
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

    public function render(Stage $stage, Action $action, Scene $scene): void
    {
        $character = $stage->owner;
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
    }
}