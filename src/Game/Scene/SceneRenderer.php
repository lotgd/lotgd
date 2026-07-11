<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;

/**
 * Responsible class to render a scene onto the stage.
 */
readonly class SceneRenderer
{

    public function __construct(
        private SceneRepository $sceneRepository,
        private DiceBagInterface $diceBag,
        private ActionService $actionService,
        private LoggerInterface $logger,
        private SceneService $sceneService,
        private ExpressionService $expressionService,
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

        $newStage = new Stage(
            owner: $character,
        );
        $character->stage = $newStage;

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
        $stage->scene = $scene;
        $stage->title = $scene->title;

        $stage->clear();

        $stage->addParagraph(new Paragraph(
            id: Stage::SceneText,
            text: $scene->description
        ));

        $this->actionService->resetActionGroups($stage);
        $this->addActions($stage, $scene);

        return $stage;
    }

    /**
     * Internal helper method. Creates an action to switch scenes based on a connection.
     *
     * @param Scene $scene
     * @param SceneConnection $sceneConnection
     * @param Character $character
     * @return ?Action
     * @internal
     */
    public function createActionFromConnection(
        Scene $scene,
        SceneConnection $sceneConnection,
        Character $character,
    ): ?Action {
        $action = new Action(diceBag: $this->diceBag);
        $add = true;

        if ($sceneConnection->sourceScene === $scene) {
            $action->title = $sceneConnection->sourceLabel ?? $sceneConnection->targetScene->title;
            $action->sceneId = $sceneConnection->targetScene;

            if (!$this->expressionService->evaluateBoolean($character, $sceneConnection->sourceExpression)) {
                $add = false;
            }
        } elseif ($sceneConnection->targetScene === $scene) {
            $action->title = $sceneConnection->targetLabel ?? $sceneConnection->sourceScene->title;
            $action->sceneId = $sceneConnection->sourceScene;

            if (!$this->expressionService->evaluateBoolean($character, $sceneConnection->targetExpression)) {
                $add = false;
            }
        } else {
            // This should never be reached, as $scene must either be the source or the target scene of a connection,
            // otherwise, it should not be in the list.
            $action->title = "#invalidConnection:{$sceneConnection->id}";
        }

        return $add ? $action : null;
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
        $character = $stage->owner;

        $allKnownConnection = $scene->getConnections();
        $addedConnections = [];

        foreach ($scene->actionGroups as $sceneActionGroup) {
            $actionGroup = new ActionGroup(
                id: "lotgd.actionGroup.custom.{$sceneActionGroup->id}",
                title: $sceneActionGroup->title,
                weight: $sceneActionGroup->sorting,
            );

            foreach ($sceneActionGroup->connections as $connection) {
                if (!isset($addedConnections[$connection->id])) {
                    $action = $this->createActionFromConnection($scene, $connection, $character);

                    if ($action !== null) {
                        $actionGroup->addAction($action);
                    }

                    $addedConnections[$connection->id] = true;
                }
            }

            $stage->addActionGroup($actionGroup);
        }

        // Add all other actions
        foreach ($scene->getConnections(visibleOnly: true) as $connection) {
            if (!isset($addedConnections[$connection->id])) {
                $action = $this->createActionFromConnection($scene, $connection, $character);

                if ($action !== null) {
                    $stage->addAction(ActionGroup::EMPTY, $action);
                }

                $addedConnections[$connection->id] = true;
            }
        }
    }

    public function renderOnSceneChange(Stage $stage, Scene $targetScene, Action $selectedAction): void
    {
        $this->logger->debug("Calling onSceneChange");

        $targetSceneTemplate = $this->sceneService->getTemplate($targetScene);
        $targetSceneTemplate?->setSceneChangeParameter($stage, $selectedAction, $targetScene);
        $targetSceneTemplate?->onSceneChange();
    }
}