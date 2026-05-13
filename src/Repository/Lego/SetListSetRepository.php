<?php

namespace App\Repository\Lego;

use App\Entity\Lego\SetListSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

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

    /**
     * Find set links for a board whose set matches a search query (name, number, theme, year).
     *
     * @return SetListSet[]
     */
    public function findBySetListAndQuery(string $setListId, string $query): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        $qb = $this->createQueryBuilder('sls')
            ->join('sls.set', 's')
            ->leftJoin('s.theme', 't')
            ->where('sls.setList = :setListId')
            ->setParameter('setListId', $setListId);

        $yearCondition = '';
        if (is_numeric($query) && (int) $query > 0) {
            $yearCondition = ' OR s.year = :year';
            $qb->setParameter('year', (int) $query);
        }

        $qb->andWhere(
            'LOWER(s.name) LIKE :q
             OR LOWER(s.number) LIKE :q
             OR LOWER(s.baseNumber) LIKE :q
             OR LOWER(t.name) LIKE :q'
            . $yearCondition
        )->setParameter('q', $q);

        return $qb->getQuery()->getResult();
    }

    public function findBySetNumberAndListId(string $setNumber, string $listId): ?SetListSet
    {
        return $this->createQueryBuilder('sls')
            ->join('sls.set', 's')
            ->join('sls.setList', 'sl')
            ->where('s.number = :setNumber')
            ->andWhere('sl.id = :listId')
            ->setParameter('setNumber', $setNumber)
            ->setParameter('listId', $listId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
