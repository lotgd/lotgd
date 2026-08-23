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
     * @var array<string, GameState>
     */
    private array $settingsCache {
        get {
            if (!isset($this->settingsCache)) {
                $this->settingsCache = $this->getAllSettings();
            }

            return $this->settingsCache;
        }
    }

    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, GameState::class);
    }

    /**
     * @return array<string, GameState>
     */
    public function getAllSettings(): array
    {
        return
            $this->createQueryBuilder("gs", indexBy: "gs.name")
                ->andWhere("gs.type = :type")
                ->setParameter("type", GameStateType::Setting)
                ->getQuery()
                ->getResult();
    }

    public function getSettingByName(string $name): ?GameState
    {
        return $this->settingsCache[$name] ?? null;
    }
}
