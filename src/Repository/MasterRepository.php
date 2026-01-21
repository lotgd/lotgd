<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\Master;
use LotGD2\Game\Random\DiceBagInterface;

/**
 * @extends ServiceEntityRepository<Master>
 */
class MasterRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        readonly DiceBagInterface $diceBag,
    ) {
        parent::__construct($registry, Master::class);
    }

    public function getByLevel(int $level): ?Master
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.level = :level')
            ->setParameter('level', $level)
            ->getQuery()
            ->getOneOrNullResult();
    }
}