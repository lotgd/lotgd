<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\Scene;

/**
 * @extends ServiceEntityRepository<Scene>
 */
class SceneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scene::class);
    }

    public function getDefaultScene(): ?Scene
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->andWhere('s.defaultScene = 1')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return Scene[]
     */
    public function findAllWithConnections(): iterable
    {
        return $this->createQueryBuilder("s")
            ->select('s')
            ->leftJoin('s.sourcedConnections', 'source')
            ->leftJoin('s.targetingConnections', 'target')
            ->addSelect("source", "target")
            ->getQuery()
            ->getResult();
    }
}
