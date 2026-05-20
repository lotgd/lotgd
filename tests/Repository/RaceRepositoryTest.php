<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Race;
use LotGD2\Kernel;
use LotGD2\Repository\RaceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(RaceRepository::class)]
#[UsesClass(Kernel::class)]
class RaceRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Race::class);

        $this->assertInstanceOf(RaceRepository::class, $instance);
    }
}
