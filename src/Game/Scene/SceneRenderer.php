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

/**
 * Responsible class to render a scene onto the stage.
 */
readonly class SceneRenderer
{
    public function __construct(
        private SceneRepository $sceneRepository,
        private DiceBagInterface $diceBag,
    ) {

    }

    /**
     * Creates a Stage for a character that does not have a stage set yet and fetches the default scene.
     *
     * @param Character $character
     * @return Stage
     */
    public function renderDefault(
        Character $character
    ): Stage {
        $defaultScene = $this->sceneRepository->getDefaultScene();

        $newStage = new Stage();
        $character->setStage($newStage);

        return $this->render($newStage, $defaultScene);
    }

    /**
     * Main function of this service. Sets the stage based on information from the scene.
     *
     * Note: If this function ever offers a module hook, it should make sure that it only
     *  expands on this concept and does not change the rendering completely (like exchanging the scene). This should
     *  be done before this method is called.
     * @param Stage $stage
     * @param Scene $scene
     * @return Stage
     */
    public function render(
        Stage $stage,
        Scene $scene,
    ): Stage {
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

    /**
     * Internal helper method. Creates an action to switch scenes based on a connection.
     *
     * @internal
     * @param Scene $scene
     * @param SceneConnection $sceneConnection
     * @return Action
     */
    public function createActionFromConnection(
        Scene $scene,
        SceneConnection $sceneConnection
    ): Action {
        $action = new Action(diceBag: $this->diceBag);

        if ($sceneConnection->getSourceScene() === $scene) {
            $action->title = $sceneConnection->getSourceLabel();
            $action->sceneId = $sceneConnection->getTargetScene();
        } elseif ($sceneConnection->getTargetScene() === $scene) {
            $action->title = $sceneConnection->getTargetLabel();
            $action->sceneId = $sceneConnection->getSourceScene();
        } else {
            // This should never be reached, as $scene must either be the source or the target scene of a connection,
            // otherwise, it should not be in the list.
            $action->title = "#invalidConnection:{$sceneConnection->getId()}";
        }

        return $action;
    }

    /**
     * Internal helper function. Creates action groups and actions based on connections.
     * @internal
     * @param Stage $stage
     * @param Scene $scene
     * @return void
     */
    public function addActions(
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

    /**
     * Adds default action groups ("Others" and "Hidden")
     * @param Stage $stage
     * @return void
     */
    public function addDefaultActionGroups(Stage $stage): void
    {
        $stage->addActionGroup(
            new ActionGroup()
                ->setId(ActionGroup::EMPTY)
                ->setTitle("Others")
        );

        $stage->addActionGroup(
            new ActionGroup()
                ->setId(ActionGroup::HIDDEN)
                ->setTitle("Hidden")
        );
    }
}