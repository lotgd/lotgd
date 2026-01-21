<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Repository\SceneRepository;

readonly class SceneRenderer
{
    public function __construct(
        private SceneRepository $sceneRepository,
        private DiceBagInterface $diceBag,
    ) {

    }

    public function renderDefault(
        Character $character
    ): Stage {
        // For now: Get Scene with id=1
        $defaultScene = $this->sceneRepository->getDefaultScene();

        $newStage = new Stage();
        $character->setStage($newStage);

        return $this->render($newStage, $defaultScene);
    }

    public function render(
        Stage $stage,
        Scene $scene,
    ): Stage
    {
        $stage->setScene($scene);
        $stage->setTitle($scene->getTitle());
        $stage->setDescription($scene->getDescription());
        $stage->clearAttachments();
        $stage->clearContext();

        $stage->clearActionGroups();
        $this->addDefaultActionGroups($stage);
        $this->addActions($stage, $scene);

        return $stage;
    }

    private function createActionFromConnection(
        Scene $scene,
        SceneConnection $sceneConnection
    ): Action {
        $action = new Action();
        $action->setId($this->diceBag->getRandomString(8));

        if ($sceneConnection->getSourceScene() === $scene) {
            $action->setTitle($sceneConnection->getSourceLabel());
            $action->setSceneId($sceneConnection->getTargetScene()->getId());
        } elseif ($sceneConnection->getTargetScene() === $scene) {
            $action->setTitle($sceneConnection->getTargetLabel());
            $action->setSceneId($sceneConnection->getSourceScene()->getId());
        } else {
            $action->setTitle("#invalidConnection:{$sceneConnection->getId()}");
        }

        return $action;
    }

    private function addActions(
        Stage $stage,
        Scene $scene,
    ): void {
        $allKnownConnection = $scene->getConnections();
        $addedConnections = [];

        foreach ($scene->getActionGroups() as $sceneActionGroup) {
            $actionGroup = (new ActionGroup())
                ->setId("lotgd.actionGroup.custom.{$sceneActionGroup->getId()}")
                ->setTitle($sceneActionGroup->getTitle())
                ->setWeight($sceneActionGroup->getSorting())
            ;

            foreach ($sceneActionGroup->getConnections() as $connection) {
                if (!isset($addedConnections[$connection->getId()])) {
                    $action = $this->createActionFromConnection($scene, $connection);
                    $actionGroup->addAction($action);
                    $addedConnections[$connection->getId()] = true;
                }
            }

            $stage->addActionGroup($actionGroup);
        }

        // Add all other actions
        foreach ($scene->getConnections(visibleOnly: true) as $connection) {
            if (!isset($addedConnections[$connection->getId()])) {
                $action = $this->createActionFromConnection($scene, $connection);
                $stage->addAction(ActionGroup::EMPTY, $action);
                $addedConnections[$connection->getId()] = true;
            }
        }
    }

    public function addDefaultActionGroups(Stage $stage): void
    {
        $stage->addActionGroup(
            (new ActionGroup())
                ->setId(ActionGroup::EMPTY)
                ->setTitle("Others")
        );

        $stage->addActionGroup(
            (new ActionGroup())
                ->setId(ActionGroup::HIDDEN)
                ->setTitle("Hidden")
        );
    }
}