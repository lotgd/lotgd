<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\Creature;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;

/**
 * @extends ServiceEntityRepository<Creature>
 */
class CreatureRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        readonly DiceBagInterface $diceBag,
    ) {
        parent::__construct($registry, Creature::class);
    }

    public function getRandomCreature(int $level): ?Creature
    {
        $creatureRows = $this->createQueryBuilder('c')
            ->select("COUNT(c.id)")
            ->where('c.level = :level')
            ->setParameter('level', $level)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        $number = $this->diceBag->throw(0, $creatureRows-1);

        return $this->createQueryBuilder('c')
            ->where('c.level = :level')
            ->setParameter('level', $level)
            ->setFirstResult($number)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}