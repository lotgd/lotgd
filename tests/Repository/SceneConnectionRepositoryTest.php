<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\SceneConnection;
use LotGD2\Repository\SceneConnectionRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(SceneConnectionRepository::class)]
class SceneConnectionRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(SceneConnection::class);

        $this->assertInstanceOf(SceneConnectionRepository::class, $instance);
    }
}
