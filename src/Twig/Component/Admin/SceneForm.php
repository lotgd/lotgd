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
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
class SceneForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: "somethingElse")]
    public ?Scene $scene = null;

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
        $entityManager->persist($scene);
        $entityManager->flush();

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