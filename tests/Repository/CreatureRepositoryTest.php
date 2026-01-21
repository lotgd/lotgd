<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Kernel;
use LotGD2\Repository\CreatureRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CreatureRepository::class)]
#[UsesClass(Kernel::class)]
class CreatureRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->expects($this->exactly(2))->method("throw")->willReturn(5, 10);
        $kernel->getContainer()->set(DiceBagInterface::class, $diceBag);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testGetRandomCreature(): void
    {
        $repository = $this->entityManager->getRepository(Creature::class);
        $creature1 = $repository->getRandomCreature(1);
        $creature2 = $repository->getRandomCreature(1);

        $this->assertNotNull($creature1);
        $this->assertNotSame($creature1, $creature2);
    }
}
