<?php
declare(strict_types=1);

namespace LotGD2\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LotGD2\Entity\Attachment;

/**
 * @extends ServiceEntityRepository<Attachment>
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $manager)
    {
        parent::__construct($manager, Attachment::class);
    }
}