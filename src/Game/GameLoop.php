<?php
declare(strict_types=1);

namespace LotGD2\Game;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Error\InvalidActionError;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Kernel;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsAlias(id: "lotgd2.game_loop", public: true)]
#[Autoconfigure(public: true)]
class GameLoop
{
    private ContainerInterface $container;

    private ?Character $character = null;

    public function __construct(
        readonly private Kernel $kernel,
        readonly private EntityManagerInterface $entityManager,
        readonly private LoggerInterface $logger,
        readonly private SceneRenderer $renderer,
        readonly private SceneRepository $sceneRepository,
        readonly private ActionService $actionService,
    ) {
        $this->container = $this->kernel->getContainer();
    }

    public function getCharacter(): Character
    {
        return $this->character;
    }

    public function setCharacter(Character $character): self
    {
        $this->character = $character;
        return $this;
    }

    public function save(Stage $stage): void
    {
        $this->entityManager->persist($stage);
        $this->entityManager->flush();
    }

    public function getStage(Character $character): Stage
    {
        $stage = $character->stage;

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
        $this->logger->debug("Take action{$action} for character with id={$character->id}");

        $stage = $character->stage;
        $currentScene = $stage->scene;
        $renderDefault = true;

        $selectedAction = $this->actionService->getActionById($stage, $action);

        if (!$selectedAction) {
            throw new InvalidActionError("The action with the id {$action} is not valid.");
        }

        $targetScene = $this->sceneRepository->find($selectedAction->sceneId);

        if ($targetScene !== $currentScene) {
            if ($currentScene->templateClass !== null
                and (bool)($selectedAction->getParameters()["lotgd.scene.skipOnSceneLeave"] ?? false) === false
            ) {
                $this->logger->debug("Calling onSceneLeave");

                // Clear actions
                $stage->clearActionGroups();
                $this->renderer->addDefaultActionGroups($stage);

                // Connect stage to target stage
                $selectedAction->title = "Continue";
                $selectedAction->setParameter("lotgd.loop.skipOnSceneLeave", true);
                $stage->addAction(ActionGroup::EMPTY, $selectedAction);

                /** @var SceneTemplateInterface<array<string, mixed>> $currentSceneTemplate */
                $currentSceneTemplate = $this->container->get($currentScene->templateClass);
                $reply = $currentSceneTemplate->onSceneLeave($stage, $selectedAction, $currentScene, $targetScene);

                $renderDefault = !$reply;
            } elseif ($targetScene->templateClass !== null
                and (bool)($selectedAction->getParameters()["lotgd.scene.skipOnSceneEnter"] ?? false) === false
            ) {
                $this->logger->debug("Calling onSceneEnter");

                /** @var SceneTemplateInterface<array<string, mixed>> $currentSceneTemplate */
                $targetSceneTemplate = $this->container->get($targetScene->templateClass);  // @phpstan-ignore varTag.differentVariable
                $reply = $targetSceneTemplate->onSceneEnter($stage, $selectedAction, $currentScene, $targetScene);

                $renderDefault = !$reply;
            }
        }

        if ($renderDefault) {
            $this->logger->debug("Rendering scene");

            $stage = $this->renderer->render($character->stage, $targetScene);

            // Allow the scene template to change the scene
            if ($targetScene->templateClass) {
                $this->logger->debug("Calling onSceneChange");

                /** @var SceneTemplateInterface<array<string, mixed>> $currentSceneTemplate */
                $targetSceneTemplate = $this->container->get($targetScene->templateClass);
                $targetSceneTemplate?->onSceneChange($stage, $selectedAction, $targetScene);  // @phpstan-ignore nullsafe.neverNull
            }
        }

        $this->save($stage);

        return $stage;
    }
}