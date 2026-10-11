<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getName());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getPlainPassword());
        $this->assertSame([], $user->getRoles());
    }

    public function testIdGetter(): void
    {
        $user = new User();
        $this->assertNull($user->getId());

        $reflection = new ReflectionProperty(User::class, "id");
        $reflection->setValue($user, 42);

        $this->assertSame(42, $user->getId());
    }

    public function testNameGetterAndSetter(): void
    {
        $user = new User();

        $result = $user->setName("JohnDoe");
        $this->assertSame($user, $result);
        $this->assertSame("JohnDoe", $user->getName());

        $user->setName(null);
        $this->assertNull($user->getName());
    }

    public function testEmailGetterAndSetter(): void
    {
        $user = new User();

        $result = $user->setEmail("john@example.com");
        $this->assertSame($user, $result);
        $this->assertSame("john@example.com", $user->getEmail());
        $this->assertSame("john@example.com", $user->getUserIdentifier());

        $user->setEmail(null);
        $this->assertNull($user->getEmail());
    }

    public function testPasswordGetterAndSetter(): void
    {
        $user = new User();

        $result = $user->setPassword("hashed_secret");
        $this->assertSame($user, $result);
        $this->assertSame("hashed_secret", $user->getPassword());
    }

    public function testPlainPasswordAndEraseCredentials(): void
    {
        $user = new User();

        $result = $user->setPlainPassword("plain_secret");
        $this->assertSame($user, $result);
        $this->assertSame("plain_secret", $user->getPlainPassword());

        $user->eraseCredentials();
        $this->assertNull($user->getPlainPassword());
    }

    public function testRolesManagement(): void
    {
        $user = new User();

        $result = $user->setRoles(["ROLE_USER"]);
        $this->assertSame($user, $result);
        $this->assertSame(["ROLE_USER"], $user->getRoles());

        // Add new role
        $resultAdd = $user->addRole("ROLE_ADMIN");
        $this->assertSame($user, $resultAdd);
        $this->assertSame(["ROLE_USER", "ROLE_ADMIN"], $user->getRoles());

        // Add duplicate role should not duplicate
        $user->addRole("ROLE_ADMIN");
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame(["ROLE_USER", "ROLE_ADMIN"], $user->getRoles());

        // Remove existing role
        $resultRemove = $user->removeRole("ROLE_ADMIN");
        $this->assertSame($user, $resultRemove);
        $this->assertEquals(["ROLE_USER"], array_values($user->getRoles()));

        // Remove non-existent role does nothing
        $user->removeRole("ROLE_SUPER_ADMIN");
        $this->assertEquals(["ROLE_USER"], array_values($user->getRoles()));
    }
}
