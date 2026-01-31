<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Form\Scene\SceneConnectionType;
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
class SceneConnectionForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public ?string $key;

    #[LiveProp(updateFromParent: true)]
    public SceneConnection $sceneConnection;

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

        $sceneConnection = $this->getForm()->getData();

        $entityManager->persist($sceneConnection);
        $entityManager->flush();

        $this->saved = true;
    }

    /**
     * @return FormInterface<Scene>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SceneConnectionType::class, $this->sceneConnection, options: [
            "source_action_groups" => $this->sceneConnection->sourceScene->actionGroups,
            "target_action_groups" => $this->sceneConnection->targetScene->actionGroups,
        ]);
    }
}