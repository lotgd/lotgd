<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneAttachment;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait DefaultAttachmentTrait
{
    /**
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $config;

    #[LiveProp]
    public Character $character;

    #[LiveProp]
    public Stage $stage;

    private function checkAttachment(): void
    {
        if (count(array_filter(
                $this->stage->getAttachments(),
                fn ($attachment) => $attachment["attachment"]->getAttachmentClass() === self::class
            )) === 0) {

            throw new AccessDeniedException();
        }
    }
}