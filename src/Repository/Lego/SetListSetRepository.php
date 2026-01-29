<?php

namespace App\Repository\Lego;

use App\Entity\Lego\SetListSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SetListSet>
 *
 * @method SetListSet|null find($id, $lockMode = null, $lockVersion = null)
 * @method SetListSet|null findOneBy(array $criteria, array $orderBy = null)
 * @method SetListSet[]    findAll()
 * @method SetListSet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SetListSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetListSet::class);
    }
}
