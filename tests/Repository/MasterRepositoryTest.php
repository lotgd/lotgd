<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Master;
use LotGD2\Repository\MasterRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(MasterRepository::class)]
#[UsesClass(Master::class)]
class MasterRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    #[TestWith([1, 1])]
    #[TestWith([6, 6])]
    #[TestWith([10, 10])]
    #[TestWith([14, 14])]
    #[TestWith([15, null])]
    public function testGetMasterForLevel(int $level, ?int $shouldBeLevel): void
    {
        $repository = $this->entityManager->getRepository(Master::class);
        $master = $repository->getByLevel($level);

        $this->assertSame($shouldBeLevel, $master->level);
    }
}
