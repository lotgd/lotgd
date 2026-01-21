<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Stage;
use LotGD2\Repository\StageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(StageRepository::class)]
class StageRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Stage::class);

        $this->assertInstanceOf(StageRepository::class, $instance);
    }
}
