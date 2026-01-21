<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Character;
use LotGD2\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserAndCharacterFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }

    /**
     * @return Character[]
     */
    protected function getCharacters(): array
    {
        return [
            (new Character())
                ->setName("E. Fischer")
                ->setTitle("Apprentice")
                ->setLevel(1),
            (new Character())
                ->setName("T. Sandmeyer")
                ->setTitle("Charged")
                ->setLevel(9),
            (new Character())
                ->setName("A. Suzuki")
                ->setTitle("Sensei")
                ->setLevel(14),
            (new Character())
                ->setName("M. Disney")
                ->setTitle("Master")
                ->setLevel(15),
        ];
    }

    /**
     * @return User[]
     */
    protected function getUsers(): array
    {
        return [
            (new User())
                ->setEmail("admin@example.com")
                ->setName("Admin")
                ->setPlainPassword("CHANGEME")
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getCharacters() as $character) {
            $manager->persist($character);
        }

        foreach ($this->getUsers() as $user) {
            $user->setPassword($this->hasher->hashPassword($user, $user->getPlainPassword()));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
