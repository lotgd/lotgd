<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Repository\AttachmentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AttachmentRepository::class)]
class AttachmentRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingConstructorFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Attachment::class);

        $this->assertInstanceOf(AttachmentRepository::class, $instance);
    }
}
