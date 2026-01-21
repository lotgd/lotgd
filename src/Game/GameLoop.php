<?php
declare(strict_types=1);

namespace LotGD2\Game;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Character;
use LotGD2\Entity\Stage;
use LotGD2\Game\Error\InvalidActionError;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Kernel;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

readonly class GameLoop
{
    private ContainerInterface $container;

    public function __construct(
        private Kernel $kernel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private SceneRenderer $renderer,
        private SceneRepository $sceneRepository,
        private ActionService $actionService,
    ) {
        $this->container = $this->kernel->getContainer();
    }

    public function save(Stage $stage): void
    {
        $this->entityManager->persist($stage);
        $this->entityManager->flush();
    }

    public function getStage(Character $character): Stage
    {
        $stage = $character->getStage();

        if (!$stage) {
            $stage = $this->renderer->renderDefault($character);
            $this->save($stage);
        }

        return $stage;
    }

    public function takeAction(
        Character $character,
        string $action,
    ): Stage {
        $this->logger->debug("Take action{$action} for character with id={$character->getId()}");

        $stage = $character->getStage();
        $currentScene = $stage->getScene();
        $renderDefault = true;

        $selectedAction = $this->actionService->getActionById($stage, $action);

        if (!$selectedAction) {
            throw new InvalidActionError("The action with the id {$action} is not valid.");
        }

        $targetScene = $this->sceneRepository->find($selectedAction->getSceneId());

        if ($targetScene !== $currentScene) {
            if ($currentScene->getTemplateClass() !== null
                and (bool)($selectedAction->getParameters()["lotgd.scene.skipOnSceneLeave"] ?? false) === true
            ) {
                $this->logger->debug("Calling onSceneLeave");

                // Clear actions
                $stage->clearActionGroups();
                $this->renderer->addDefaultActionGroups($stage);

                // Connect stage to target stage
                $selectedAction->setTitle("Continue");
                $selectedAction->setParameter("lotgd.loop.skipOnSceneLeave", true);
                $stage->addAction(ActionGroup::EMPTY, $selectedAction);

                /** @var SceneTemplateInterface $currentSceneTemplate */
                $currentSceneTemplate = $this->container->get($currentScene->getTemplateClass());
                $reply = $currentSceneTemplate->onSceneLeave($stage, $selectedAction, $currentScene);

                if ($reply === true) {
                    $renderDefault = false;
                }
            } elseif ($targetScene->getTemplateClass() !== null
                and (bool)($selectedAction->getParameters()["lotgd.scene.skipOnSceneLeave"] ?? false) === true
            ) {
                $this->logger->debug("Calling onSceneEnter");

                $stage = $this->renderer->render($character->getStage(), $targetScene);

                /** @var SceneTemplateInterface $currentSceneTemplate */
                $targetSceneTemplate = $this->container->get($targetScene->getTemplateClass());  // @phpstan-ignore varTag.differentVariable
                $reply = $targetSceneTemplate->onSceneEnter($stage, $selectedAction, $targetScene);

                if ($reply === true) {
                    $renderDefault = false;
                }
            }
        }

        if ($renderDefault) {
            $this->logger->debug("Rendering scene");

            $stage = $this->renderer->render($character->getStage(), $targetScene);

            // Allow the scene template to change the scene
            if ($targetScene->getTemplateClass()) {
                $this->logger->debug("Calling onSceneChange");

                /** @var SceneTemplateInterface $currentSceneTemplate */
                $targetSceneTemplate = $this->container->get($targetScene->getTemplateClass());
                $targetSceneTemplate?->onSceneChange($stage, $selectedAction, $targetScene);  // @phpstan-ignore nullsafe.neverNull
            }
        }

        $this->save($stage);

        return $stage;
    }
}