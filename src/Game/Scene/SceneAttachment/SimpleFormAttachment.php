<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneAttachment;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'Attachment::SimpleFormAttachment',
    template: "component/Scene/SceneAttachment/SimpleFormAttachment.html.twig",
)]
final class SimpleFormAttachment extends AbstractController implements SceneAttachmentInterface
{
    use DefaultActionTrait;
    use DefaultAttachmentTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    const string ActionParameterName = "SimpleFormAttachment::FormData";

    #[LiveProp]
    public ?array $initialFormData = null;

    public function __construct(
    ) {
    }

    #[LiveAction]
    public function save(
        ActionService $actionService,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
    ) {
        $this->submitForm();

        $actionId = (string)($this->config["actionId"] ?? "");
        $action = $actionService->getActionById($this->stage, $actionId);

        if (!$action) {
            $logger->critical("Unknown action id in SimpleFormAttachment::buyItem() for character {$this->character->getId()}");
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Modify action
        $action->setParameter(self::ActionParameterName, $this->getForm()->getData());
        $entityManager->persist($this->stage);
        $entityManager->flush();

        $this->emit("takeAction", ["actionId" => $actionId]);
    }

    protected function instantiateForm(): FormInterface
    {
        $form = $this->createFormBuilder();
        foreach ($this->config["form"] as [$name, $type, $config]) {
            $form->add($name, $type, $config);
        }

        return $form->getForm();
    }
}