<?php

namespace App\Repository\Lego;

use App\Entity\Lego\PartColor;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetPart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

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

    public function findBySetNumberPartAndColor(string $setNumber, string $partNumber, int $colorId): ?SetPart
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.model', 'm')
            ->join('sp.partColor', 'pc')
            ->join('pc.part', 'p')
            ->join('pc.color', 'c')
            ->where('m.number = :setNumber')
            ->andWhere('p.partNumber = :partNumber')
            ->andWhere('c.id = :colorId')
            ->setParameter('setNumber', $setNumber)
            ->setParameter('partNumber', $partNumber)
            ->setParameter('colorId', $colorId)
            ->getQuery()
            ->getOneOrNullResult();
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

    /**
     * Finds a specific SetPart entity based on the provided model and part color.
     *
     * @param Set|null $model The model associated with the SetPart entity.
     * @param PartColor|null $partColor The part color associated with the SetPart entity.
     *
     * @return SetPart|null The SetPart entity matching the specified model and part color, or null if none is found.
     */
    public function findOneByModelAndPartColor(?Set $model, ?PartColor $partColor): ?SetPart
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.model', 'm')
            ->addSelect('m')
            ->leftJoin('sp.partColor', 'pc')
            ->addSelect('pc')
            ->andWhere('sp.model = :model')
            ->setParameter('model', $model)
            ->andWhere('sp.partColor = :partColor')
            ->setParameter('partColor', $partColor)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
