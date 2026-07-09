<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Form\Scene\SceneType;
use LotGD2\Game\Scene\SceneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
class SceneForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public ?string $key;

    #[LiveProp(fieldName: "somethingElse")]
    public ?Scene $scene = null;

    #[LiveProp(updateFromParent: true)]
    public ?Scene $parentScene = null;

    #[LiveProp]
    public ?bool $saved = null;

    public function __construct(
        private SceneService $sceneService,
    ) {

    }

    public function __invoke(): void
    {
        $this->saved = false;
    }

    #[LiveAction]
    public function save(
        EntityManagerInterface $entityManager,
    ): void {
        $this->submitForm();

        /** @var Scene $scene */
        $scene = $this->getForm()->getData();

        $sceneId = $scene->id;

        // If there is a parent scene, we'll persist it.
        $this->parentScene?->connectTo($scene);

        $this->sceneService->addTags($scene);

        $entityManager->persist($scene);
        $entityManager->flush();

        if (!$sceneId) {
            $this->resetForm();
            $this->emitUp("sceneAdded", ["scene" => $scene->id]);
        }

        $this->saved = true;
    }

    /**
     * @return FormInterface<Scene>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SceneType::class, $this->scene);
    }
}