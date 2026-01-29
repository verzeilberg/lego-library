<?php

namespace App\Repository\Lego;

use App\Entity\Lego\Color;
use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PartColor>
 */
class PartColorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartColor::class);
    }

    /**
     * Finds the association between a part and a color based on the given part and color parameters.
     *
     * @param mixed $part The part entity or identifier used to query the database.
     * @param mixed $color The color entity or identifier used to query the database.
     *
     * @return mixed|null The matching part-color association or null if no match is found.
     */
    public function findPartColorByPartAndColor(Part $part, Color $color): ?PartColor
    {
        return $this->createQueryBuilder('pc')
            ->join('pc.part', 'p')
            ->join('pc.color', 'c')
            ->where('p.partNumber = :partNumber')
            ->andWhere('c.id = :colorId')
            ->setParameter('partNumber', $part->getPartNumber())
            ->setParameter('colorId', $color->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
