<?php

namespace App\Repository\Lego;

use App\Entity\Lego\SetRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SetRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetRating::class);
    }

    // Get all ratings for a user
    public function findByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Get the overall average rating for a set
    public function getOverallRatingForSet($set): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('AVG(r.value) as avg_rating')
            ->andWhere('r.set = :set')
            ->setParameter('set', $set)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Get a specific user's rating for a set
    public function getUserRatingForSet($user, $set): ?int
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.value')
            ->andWhere('r.user = :user')
            ->andWhere('r.set = :set')
            ->setParameter('user', $user)
            ->setParameter('set', $set)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['value'] ?? null;
    }

    public function findUserSetsWithRatings(int $userId): array
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('s.number AS setNumber')
            ->addSelect('s.name AS setName')
            ->addSelect('userRating.value AS personalRating')
            ->addSelect('AVG(overallRating.value) AS overallRating')
            ->from('App\Entity\Lego\SetListSet', 'link')
            ->join('link.set', 's')
            ->leftJoin('App\Entity\Lego\SetRating', 'userRating', 'WITH', 'userRating.set = s AND userRating.user = :user')
            ->leftJoin('App\Entity\Lego\SetRating', 'overallRating', 'WITH', 'overallRating.set = s')
            ->where('link.setList IN (
            SELECT sl.id FROM App\Entity\Lego\SetList sl WHERE sl.userData = :user
        )')
            ->setParameter('user', $userId)
            ->groupBy('s.number, s.name, userRating.value')
            ->orderBy('s.name', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

}
