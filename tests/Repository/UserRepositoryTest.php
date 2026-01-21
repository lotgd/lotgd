<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\User;
use LotGD2\Kernel;
use LotGD2\Repository\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(UserRepository::class)]
#[UsesClass(Kernel::class)]
class UserRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingRepositoryFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(User::class);

        $this->assertInstanceOf(UserRepository::class, $instance);
    }

    public function testloadUserByIdentifier(): void
    {
        $instance = $this->entityManager->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $instance);

        $user = $instance->loadUserByIdentifier("admin@example.com");

        $this->assertInstanceOf(User::class, $user);
    }
}
