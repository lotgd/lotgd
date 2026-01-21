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
use LotGD2\Kernel;
use LotGD2\Repository\SceneRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

readonly class GameLoop
{
    private ContainerInterface $container;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Kernel $kernel,
        private SceneRenderer $renderer,
        private SceneRepository $sceneRepository,
    ) {
        $this->container = $this->kernel->getContainer();
    }

    public function save()
    {
        $this->entityManager->flush();
    }

    public function getStage(Character $character): Stage
    {
        $stage = $character->getStage();

        if (!$stage) {
            $stage = $this->renderer->renderDefault($character);
            $this->save();
        }

        return $stage;
    }

    public function takeAction(
        Character $character,
        string $action,
    ): Stage {
        $currentActionGroups = $character->getStage()->getActionGroups();
        $stage = $character->getStage();
        $currentScene = $stage->getScene();
        $selectedAction = null;
        $renderDefault = true;

        foreach ($currentActionGroups as $actionGroup) {
            foreach ($actionGroup->getActions() as $actionEntry) {
                if ($actionEntry->getId() === $action) {
                    $selectedAction = $actionEntry;
                    break;
                }
            }
        }

        if (!$selectedAction) {
            throw new InvalidActionError("The action with the id {$action} is not valid.");
        }

        $targetScene = $this->sceneRepository->find($selectedAction->getSceneId());

        if ($targetScene !== $currentScene) {
            if ($currentScene->getTemplateClass() !== null
                and $selectedAction->getParameters()->get("lotgd.scene.skipOnSceneLeave", false)->asBool() === true
            ) {
                // Clear actions
                $stage->clearActionGroups();
                $this->renderer->addDefaultActionGroups($stage);

                // Connect stage to target stage
                $selectedAction->setTitle("Continue");
                $selectedAction->getParameters()->set("lotgd.loop.skipOnSceneLeave", true);
                $stage->addAction(ActionGroup::EMPTY, $selectedAction);

                /** @var SceneTemplateInterface $currentSceneTemplate */
                $currentSceneTemplate = $this->container->get($currentScene->getTemplateClass());
                $reply = $currentSceneTemplate->onSceneLeave($stage, $selectedAction, $currentScene);

                if ($reply === true) {
                    $renderDefault = false;
                }
            } elseif ($targetScene->getTemplateClass() !== null
                and $selectedAction->getParameters()->get("lotgd.scene.skipOnSceneEnter", false)->asBool() === true
            ) {
                $stage = $this->renderer->render($character->getStage(), $targetScene);

                /** @var SceneTemplateInterface $currentSceneTemplate */
                $targetSceneTemplate = $this->container->get($targetScene->getTemplateClass());
                $reply = $targetSceneTemplate->onSceneEnter($stage, $selectedAction, $targetScene);

                if ($reply === true) {
                    $renderDefault = false;
                }
            }
        }

        if ($renderDefault) {
            $stage = $this->renderer->render($character->getStage(), $targetScene);

            // Allow the scene template to change the scene
            if ($targetScene->getTemplateClass()) {
                $targetSceneTemplate = $this->container->get($targetScene->getTemplateClass(), ContainerInterface::NULL_ON_INVALID_REFERENCE);
                $targetSceneTemplate?->onSceneChange($stage, $selectedAction, $targetScene);
            }
        }

        $this->save();

        return $stage;
    }
}