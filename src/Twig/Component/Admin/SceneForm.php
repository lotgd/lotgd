<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Form\SceneType;
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

    public function __invoke(): void
    {
        $this->saved = false;
    }

    #[LiveAction]
    public function save(
        EntityManagerInterface $entityManager,
    ): void {
        $this->submitForm();

        $scene = $this->getForm()->getData();

        $sceneId = $scene->id;

        // If there is a parent scene, we'll persist it.
        if ($this->parentScene) {
            $this->parentScene->connectTo($scene);
        }

        $entityManager->persist($scene);
        $entityManager->flush();

        if (!$sceneId) {
            dump($scene->id);
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