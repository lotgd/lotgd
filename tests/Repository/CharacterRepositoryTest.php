<?php
declare(strict_types=1);

namespace LotGD2\Tests\Repository;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Kernel;
use LotGD2\Repository\CharacterRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CharacterRepository::class)]
#[UsesClass(Kernel::class)]
class CharacterRepositoryTest extends KernelTestCase
{
    use EntityManagerSetupTrait;

    public function testGettingConstructorFromEntityClass(): void
    {
        $instance = $this->entityManager->getRepository(Character::class);

        $this->assertInstanceOf(CharacterRepository::class, $instance);
    }
}
