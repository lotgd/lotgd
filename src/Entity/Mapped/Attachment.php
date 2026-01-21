<?php
declare(strict_types=1);

namespace LotGD2\Entity\Mapped;

use Doctrine\ORM\Mapping as ORM;
use LotGD2\Game\Scene\SceneAttachment\SceneAttachmentInterface;
use LotGD2\Repository\AttachmentRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use ValueError;

#[ORM\Entity(AttachmentRepository::class)]
#[ORM\UniqueConstraint(fields: ["attachmentClass"])]
#[UniqueEntity("attachmentClass")]
class Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null {
        get => $this->id;
    }

    public function __construct(
        #[ORM\Column(nullable: false)]
        #[Assert\NotBlank]
        public ?string $name = null,

        #[ORM\Column(nullable: false)]
        #[Assert\NotBlank]
        public ?string $attachmentClass = null {
            get => $this->attachmentClass;
            set(?string $value) {
                if (!is_null($value) and !is_a($value, SceneAttachmentInterface::class, true)) {
                    throw new ValueError("The class of an attachment must implement ".SceneAttachmentInterface::class.".");
                }

                $this->attachmentClass = $value;
            }
        },
    ) {

    }
}
