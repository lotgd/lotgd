<?php
declare(strict_types=1);

namespace LotGD2\Game\Stage;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Stage;

class ActionService
{
    public function getActionById(Stage $stage, string $action): ?Action
    {
        $currentActionGroups = $stage->getActionGroups();
        $selectedAction = null;

        foreach ($currentActionGroups as $actionGroup) {
            foreach ($actionGroup->getActions() as $actionEntry) {
                if ($actionEntry->getId() === $action) {
                    $selectedAction = $actionEntry;
                    break;
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