<?php

namespace App\Repository\Lego;

use App\Entity\Lego\SetPart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SetPart>
 */
class SetPartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetPart::class);
    }

    /**
     * Example: find all parts for a given model ID.
     */
    public function findByModelId(int $modelId): array
    {
        return $this->createQueryBuilder('mp')
            ->join('mp.model', 'm')
            ->andWhere('m.id = :modelId')
            ->setParameter('modelId', $modelId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Example: find how many specific parts a model has.
     */
    public function findQuantityForModelAndPart(int $modelId, int $partId): ?int
    {
        $result = $this->createQueryBuilder('mp')
            ->select('mp.quantity')
            ->join('mp.model', 'm')
            ->join('mp.part', 'p')
            ->andWhere('m.id = :modelId')
            ->andWhere('p.id = :partId')
            ->setParameter('modelId', $modelId)
            ->setParameter('partId', $partId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['quantity'] ?? null;
    }
}
