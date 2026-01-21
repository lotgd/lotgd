<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Scene;
use LotGD2\Kernel;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(SceneRepository::class)]
#[UsesClass(Kernel::class)]
class SceneRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Scene::class);

        $this->assertInstanceOf(SceneRepository::class, $instance);
    }

    public function testGetDefaultScene(): void
    {
        $instance = $this->entityManager->getRepository(Scene::class);

        $defaultScene = $instance->getDefaultScene();
        $this->assertNotNull($defaultScene);
    }
}
