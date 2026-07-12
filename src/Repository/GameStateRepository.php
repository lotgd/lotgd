<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\GameState;
use LotGD2\Entity\Mapped\Master;

/**
 * @extends ServiceEntityRepository<GameState>
 */
class GameStateRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Master::class);
    }

    public function getByName(string $name): GameState
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
