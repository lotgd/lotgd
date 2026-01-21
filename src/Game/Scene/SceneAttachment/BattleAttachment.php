<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneAttachment;

use LotGD2\Entity\Battle\BattleState;
use LotGD2\Game\Battle\Battle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'Attachment::BattleAttachment',
    template: "component/Scene/SceneAttachment/BattleAttachment.html.twig",
)]
final class BattleAttachment extends AbstractController implements SceneAttachmentInterface
{
    use DefaultActionTrait;
    use DefaultAttachmentTrait;

    #[LiveProp(useSerializerForHydration: true)]
    public BattleState $battleState;

    public function __construct(
    ) {
    }
}