<?php

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\SceneConnection;

/**
 * @extends ServiceEntityRepository<SceneConnection>
 *
 * @method SceneConnection|null find($id, $lockMode = null, $lockVersion = null)
 * @method SceneConnection|null findOneBy(array $criteria, array $orderBy = null)
 * @method SceneConnection[]    findAll()
 * @method SceneConnection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SceneConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SceneConnection::class);
    }

//    /**
//     * @return SceneConnection[] Returns an array of SceneConnection objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SceneConnection
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
