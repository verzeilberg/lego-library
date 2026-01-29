<?php

namespace App\Repository\Lego;

use App\Entity\Lego\Set;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Set>
 */
class SetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Set::class);
    }

    /**
     *
     */
    public function findBySetListId(int $setListId): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.setLists', 'sl')
            ->where('sl.id = :listId')
            ->setParameter('listId', $setListId)
            ->getQuery()
            ->getResult();
    }
}
