<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Specialty;
use LotGD2\Kernel;
use LotGD2\Repository\SpecialtyRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(SpecialtyRepository::class)]
#[UsesClass(Kernel::class)]
class SpecialtyRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Specialty::class);

        $this->assertInstanceOf(SpecialtyRepository::class, $instance);
    }
}