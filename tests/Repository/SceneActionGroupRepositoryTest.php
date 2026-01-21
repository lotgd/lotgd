<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\SceneActionGroup;
use LotGD2\Repository\SceneActionGroupRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(SceneActionGroupRepository::class)]
class SceneActionGroupRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(SceneActionGroup::class);

        $this->assertInstanceOf(SceneActionGroupRepository::class, $instance);
    }
}
