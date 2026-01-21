<?php

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\SceneActionGroup;

/**
 * @extends ServiceEntityRepository<SceneActionGroup>
 *
 * @method SceneActionGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method SceneActionGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method SceneActionGroup[]    findAll()
 * @method SceneActionGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SceneActionGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SceneActionGroup::class);
    }

//    /**
//     * @return SceneActionGroup[] Returns an array of SceneActionGroup objects
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

//    public function findOneBySomeField($value): ?SceneActionGroup
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
