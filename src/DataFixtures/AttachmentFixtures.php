<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Game\Scene\SceneAttachment\SimpleFormAttachment;
use LotGD2\Game\Scene\SceneAttachment\SimpleShopAttachment;

class AttachmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $attachments = [
            new Attachment("Simple Shop", SimpleShopAttachment::class),
            new Attachment("Simple Form", SimpleFormAttachment::class),
        ];

        foreach ($attachments as $attachment) {
            $manager->persist($attachment);
        }

        $manager->flush();
    }
}