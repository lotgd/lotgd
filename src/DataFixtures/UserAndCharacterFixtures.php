<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\User;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
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
                    Health::HealthPropertyName => 90,
                    Health::MaxHealthPropertyName => 90,
                    Health::Age => 67,
                    Stats::AttackPropertyName => 9,
                    Stats::DefensePropertyName => 9,
                    Equipment::PropertyName => [
                        Equipment::WeaponSlot => new EquipmentItem(
                            name: "Bottle of sodium nitrite",
                            strength: 9,
                            value: 4230,
                        ),
                        Equipment::ArmorSlot => new EquipmentItem(
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
                    Health::HealthPropertyName => 140,
                    Health::MaxHealthPropertyName => 140,
                    Health::Age => 80,
                    Stats::AttackPropertyName => 14,
                    Stats::DefensePropertyName => 14,
                    Equipment::PropertyName => [
                        Equipment::WeaponSlot => new EquipmentItem(
                            name: "Transmetallation",
                            strength: 14,
                            value: 9000,
                        ),
                        Equipment::ArmorSlot => new EquipmentItem(
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
                    Health::HealthPropertyName => 150,
                    Health::MaxHealthPropertyName => 150,
                    Health::Age => 71,
                    Stats::AttackPropertyName => 15,
                    Stats::DefensePropertyName => 15,
                    Equipment::PropertyName => [
                        Equipment::WeaponSlot => new EquipmentItem(
                            name: "mRNA",
                            strength: 15,
                            value: 10350,
                        ),
                        Equipment::ArmorSlot => new EquipmentItem(
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
