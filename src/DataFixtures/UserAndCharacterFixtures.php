<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\User;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\StatsHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
            new Character(
                name: "E. Fischer",
                title: "Apprentice",
                level: 1,
            ),
            new Character(
                name: "T. Sandmeyer",
                title: "Charged",
                level: 9,
                properties: [
                    HealthHandler::HealthPropertyName => 90,
                    HealthHandler::MaxHealthPropertyName => 90,
                    HealthHandler::Age => 67,
                    StatsHandler::AttackPropertyName => 9,
                    StatsHandler::DefensePropertyName => 9,
                    EquipmentHandler::PropertyName => [
                        EquipmentHandler::WeaponSlot => new EquipmentItem(
                            name: "Bottle of sodium nitrite",
                            strength: 9,
                            value: 4230,
                        ),
                        EquipmentHandler::ArmorSlot => new EquipmentItem(
                            name: "Blast shield",
                            strength: 9,
                            value: 4230,
                        ),
                    ],
                ],
            ),
            new Character(
                name: "A. Suzuki",
                title: "Sensei",
                level: 14,
                properties: [
                    HealthHandler::HealthPropertyName => 140,
                    HealthHandler::MaxHealthPropertyName => 140,
                    HealthHandler::Age => 80,
                    StatsHandler::AttackPropertyName => 14,
                    StatsHandler::DefensePropertyName => 14,
                    EquipmentHandler::PropertyName => [
                        EquipmentHandler::WeaponSlot => new EquipmentItem(
                            name: "Transmetallation",
                            strength: 14,
                            value: 9000,
                        ),
                        EquipmentHandler::ArmorSlot => new EquipmentItem(
                            name: "Phosphine Ligand",
                            strength: 14,
                            value: 9000,
                        ),
                    ],
                ],
            ),
            new Character(
                name: "M. Disney",
                title: "Master",
                level: 15,
                properties: [
                    HealthHandler::HealthPropertyName => 150,
                    HealthHandler::MaxHealthPropertyName => 150,
                    HealthHandler::Age => 71,
                    StatsHandler::AttackPropertyName => 15,
                    StatsHandler::DefensePropertyName => 15,
                    EquipmentHandler::PropertyName => [
                        EquipmentHandler::WeaponSlot => new EquipmentItem(
                            name: "mRNA",
                            strength: 15,
                            value: 10350,
                        ),
                        EquipmentHandler::ArmorSlot => new EquipmentItem(
                            name: "siRNA",
                            strength: 15,
                            value: 10350,
                        ),
                    ],
                ],
            ),
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
                ->setRoles([
                    "ROLE_ADMIN",
                    "ROLE_USER",
                    "ROLE_DEBUG",
                    "ROLE_CHEATS_ENABLED",
                    "ROLE_SCENE_EDITOR",
                ])
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
