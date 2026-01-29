<?php

namespace App\Repository\Lego;

use App\Entity\Lego\Color;
use App\Entity\Lego\Part;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Color>
 */
class ColorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Color::class);
    }

    /**
     * Example: find all parts by color name.
     */
    public function findByColorName(string $colorName): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.color', 'c')
            ->andWhere('c.name = :colorName')
            ->setParameter('colorName', $colorName)
            ->getQuery()
            ->getResult();
    }
}
