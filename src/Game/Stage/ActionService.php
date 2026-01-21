<?php
declare(strict_types=1);

namespace LotGD2\Game\Stage;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Stage;

class ActionService
{
    public function getActionById(Stage $stage, string $action): ?Action
    {
        $currentActionGroups = $stage->getActionGroups();
        $selectedAction = null;

        foreach ($currentActionGroups as $actionGroup) {
            $actionEntry = $actionGroup->getActionByReference($action);

            if ($actionEntry !== null) {
                $selectedAction = $actionEntry;
                break;
            }

            foreach ($actionGroup->getActions() as $actionEntry) {
                if ($actionEntry->id === $action) {
                    $selectedAction = $actionEntry;
                    break 2;
                }
            }
        }

        return $selectedAction;
    }

    public function addHiddenAction(Stage $stage, Action $action): void
    {
        $stage->addAction(ActionGroup::HIDDEN, $action);
    }
}