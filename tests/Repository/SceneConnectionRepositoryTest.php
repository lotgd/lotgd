<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Kernel;
use LotGD2\Repository\SceneConnectionRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(SceneConnectionRepository::class)]
#[UsesClass(Kernel::class)]
class SceneConnectionRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(SceneConnection::class);

        $this->assertInstanceOf(SceneConnectionRepository::class, $instance);
    }
}
