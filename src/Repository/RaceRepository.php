<?php
declare(strict_types=1);

namespace LotGD2\Repository;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Mapped\Race;

/**
 * @extends ServiceEntityRepository<Race>
 */
class RaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Race::class);
    }
}
