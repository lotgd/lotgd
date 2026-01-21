<?php
declare(strict_types=1);

namespace LotGD2\Entity;

use Doctrine\ORM\Mapping as ORM;
use LotGD2\Game\Scene\SceneAttachment\SceneAttachmentInterface;
use LotGD2\Repository\AttachmentRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(AttachmentRepository::class)]
#[ORM\UniqueConstraint(fields: ["attachmentClass"])]
#[UniqueEntity("attachmentClass")]
class Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(nullable: false)]
        #[Assert\NotBlank()]
        private ?string $name = null,

        #[ORM\Column(nullable: false)]
        #[Assert\NotBlank()]
        private ?string $attachmentClass = null,
    ) {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAttachmentClass(): ?string
    {
        return $this->attachmentClass;
    }

    public function setAttachmentClass(?string $attachmentClass): static
    {
        if (!is_a($attachmentClass, SceneAttachmentInterface::class, true)) {
            throw new \ValueError("The class of an attachment must implement ".SceneAttachmentInterface::class.".");
        }

        $this->attachmentClass = $attachmentClass;
        return $this;
    }
}