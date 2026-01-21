<?php
declare(strict_types=1);

namespace LotGD2\Game;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Character;
use LotGD2\Entity\Stage;
use LotGD2\Game\Error\InvalidActionError;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Repository\SceneRepository;

readonly class GameLoop
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SceneRenderer $renderer,
        private SceneRepository $sceneRepository,
    ) {
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
        $scene = null;

        foreach ($currentActionGroups as $actionGroup) {
            foreach ($actionGroup->getActions() as $actionEntry) {
                if ($actionEntry->getId() === $action) {
                    $scene = $this->sceneRepository->find($actionEntry->getSceneId());
                }
            }
        }

        if (!$scene) {
            throw new InvalidActionError("The action with the id {$action} is not valid.");
        }

        $stage = $this->renderer->render($character->getStage(), $scene);
        $this->save();

        return $stage;
    }
}