<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\SceneActionGroup;

/**
 * @extends ServiceEntityRepository<SceneActionGroup>
 */
class SceneActionGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SceneActionGroup::class);
    }
}
