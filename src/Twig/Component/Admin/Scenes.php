<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use LotGD2\Entity\Mapped\Scene;
use LotGD2\Repository\SceneRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class Scenes
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Scene $scene = null;

    public function __construct(
        private readonly SceneRepository $sceneRepository,
    ) {
    }

    /**
     * @return iterable<int, Scene>
     */
    public function getScenes(): iterable
    {
        return $this->sceneRepository->findAll();
    }

    /**
     * @phpstan-type SceneNode array{
     *     scene: int,
     *     children: SceneNode[],
     * }
     * @return SceneNode
     */
    #[ExposeInTemplate]
    public function getTree(): array
    {
        $allScenes = $this->sceneRepository->findAll();

        // Build tree
        $treeRoot = array_find($allScenes, fn (Scene $scene) => $scene->defaultScene);
        $sceneList = [$treeRoot->id => true];

        $tree = [
            "scene" => $treeRoot->id,
            "title" => $treeRoot->title,
            "children" => $this->getLeaves($treeRoot, sceneList: $sceneList),
        ];

        return $tree;
    }

    private function getLeaves(Scene $scene, int $depth = 0, array &$sceneList = [], ?Scene $parent = null): iterable
    {
        $leave = [];

        if ($depth >= 10) {
            return [];
        }

        foreach ($scene->getConnections() as $connection) {
            $connectedScene = $connection->sourceScene === $scene ? $connection->targetScene : $connection->sourceScene;

            // Direct back connections are hidden
            if ($connectedScene === $parent) {
                continue;
            }

            $tree = [
                "scene" => $connectedScene->id,
                "title" => $connectedScene->title,
                "children" => isset($sceneList[$connectedScene->id]) ? null : $this->getLeaves($connectedScene, $depth + 1, $sceneList, $scene),
            ];

            $sceneList[$connectedScene->id] = true;
            $leave[] = $tree;
        }

        return $leave;
    }

    #[LiveAction]
    public function showForm(
        #[LiveArg]
        Scene $scene
    ): void {
        $this->scene = $scene;
    }
}