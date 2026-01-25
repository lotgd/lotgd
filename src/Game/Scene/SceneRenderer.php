<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\SyntaxError;

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
        $stage->description = $scene->description;
        $stage->clearAttachments();
        $stage->clearContext();

        $this->actionService->resetActionGroups($stage);
        $this->addActions($stage, $scene);

        return $stage;
    }

    /**
     * Internal helper method. Creates an action to switch scenes based on a connection.
     *
     * @internal
     * @param Scene $scene
     * @param SceneConnection $sceneConnection
     * @return ?Action
     */
    public function createActionFromConnection(
        Scene $scene,
        SceneConnection $sceneConnection,
        ExpressionService $expressionService,
    ): ?Action {
        $action = new Action(diceBag: $this->diceBag);
        $add = true;

        if ($sceneConnection->sourceScene === $scene) {
            $action->title = $sceneConnection->sourceLabel;
            $action->sceneId = $sceneConnection->targetScene;

            if (!$expressionService->evaluate($sceneConnection->sourceExpression)) {
                $add =false;
            }
        } elseif ($sceneConnection->targetScene === $scene) {
            $action->title = $sceneConnection->targetLabel;
            $action->sceneId = $sceneConnection->sourceScene;

            if (!$expressionService->evaluate($sceneConnection->targetExpression)) {
                $add =false;
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
        $equipment = new Equipment($this->logger, $character);
        $health = new Health($this->logger, $character);

        $expressionService = new ExpressionService(
            $this->logger,
            $character,
            health: $health,
            stats: new Stats($this->logger, $equipment, $health, $character),
            gold: new Gold($this->logger, $character),
            equipment: $equipment,
        );

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
                    $action = $this->createActionFromConnection($scene, $connection, $expressionService);

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
                $action = $this->createActionFromConnection($scene, $connection, $expressionService);

                if ($action !== null) {
                    $stage->addAction(ActionGroup::EMPTY, $action);
                }

                $addedConnections[$connection->id] = true;
            }
        }
    }
}