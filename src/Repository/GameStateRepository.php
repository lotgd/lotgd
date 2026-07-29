<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\GameState;
use LotGD2\Game\Enum\GameStateType;

/**
 * @extends ServiceEntityRepository<GameState>
 */
class GameStateRepository extends ServiceEntityRepository
{
    /**
     * @var array<string, GameState>|null
     */
    private ?array $settingsCache {
        get {
            if (!isset($this->settingsCache)) {
                $this->settingsCache =
                    $this->createQueryBuilder("gs", indexBy: "gs.name")
                        ->andWhere("gs.type = :type")
                        ->setParameter("type", GameStateType::Setting)
                        ->getQuery()
                        ->getResult();
            }

            return $this->settingsCache;
        }
    }

    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, GameState::class);
    }

    public function getByName(string $name): ?GameState
    {
        return $this->settingsCache[$name] ?? null;
    }
}
