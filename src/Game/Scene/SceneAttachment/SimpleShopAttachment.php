<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneAttachment;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'Attachment::SimpleShopAttachment',
    template: "component/Scene/SceneAttachment/SimpleShopAttachment.html.twig",
)]
final class SimpleShopAttachment implements SceneAttachmentInterface
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use DefaultAttachmentTrait;

    const string ActionParameterName = "SimpleShopAttachment::ItemId";

    #[LiveAction]
    public function buyItem(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ActionService $actionService,
        #[LiveArg]
        int $itemNumber,
    ): void {
        $this->checkAttachment();

        $actionId = (string)$this->config["buyActionId"] ?? "";
        $action = $actionService->getActionById($this->stage, $actionId);

        if (!$action) {
            $logger->critical("Unknown action id in SimpleShopAttachment::buyItem() for character {$this->character->getId()}");
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Modify action
        $action->setParameter(self::ActionParameterName, $itemNumber);
        $entityManager->persist($this->stage);
        $entityManager->flush();

        $this->emit("takeAction", ["actionId" => $actionId]);
    }
}