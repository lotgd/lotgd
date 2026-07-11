<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Error\GameError;
use LotGD2\Game\Error\SpecialNotFoundError;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneTemplate\SpecialTemplate;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;

class SpecialService
{
    public const string StageReferrerProperty = "lotgd2.specialService.referrer";

    public function __construct(
        private readonly SceneRepository $sceneRepository,
        private readonly DiceBagInterface $diceBag,
        private readonly SceneRenderer $renderer,
        private readonly LoggerInterface $logger,
    ) {

    }

    /**
     * @return Scene
     * @throws SpecialNotFoundError
     */
    public function getRandomSpecial(): Scene
    {
        $specials = $this->sceneRepository->findByTag(SpecialTemplate::SceneTag);

        if (empty($specials)) {
            throw new SpecialNotFoundError("There are no specials available.");
        }

        return $this->diceBag->pick($specials)[0];
    }

    /**
     * @param Stage $stage
     * @return void
     * @throws SpecialNotFoundError
     */
    public function runSpecial(Stage $stage): void
    {
        $special = $this->getRandomSpecial();

        // Set referrer scene
        $stage->setProperty(self::StageReferrerProperty, $stage->scene->id);

        // Render scene defaults
        $this->renderer->render($stage, $special);

        // Overwrite title
        $stage->title = "Something happened!";

        // Render scene template if one given
        if ($special->templateClass) {
            $this->renderer->renderOnSceneChange($stage, $special, new Action());
        } else {
            // If not, we must add the actions
            $this->addReturnAction($stage);
        }
    }

    public function addReturnAction(Stage $stage): void
    {
        $sceneId = $stage->getProperty(self::StageReferrerProperty);
        $scene = null;

        // Find the original scene
        if ($sceneId !== null) {
            $scene = $this->sceneRepository->find($sceneId);
        }

        // If not found, get default scene
        if ($scene === null) {
            $scene =  $this->sceneRepository->getDefaultScene();
        }

        // If not found, we have a problem.
        if ($scene === null) {
            $this->logger->critical("Restoration of scene {$sceneId} was not possible and default scene was not found.");
            throw new GameError("Restoration of scene {$sceneId} was not possible, and default scene was not found.");
        }

        $stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $scene,
            title: "Continue",
        ));
    }
}
