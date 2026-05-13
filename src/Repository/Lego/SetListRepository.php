<?php

namespace App\Repository\Lego;

use App\Entity\Lego\SetList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SetList>
 *
 * @method SetList|null find($id, $lockMode = null, $lockVersion = null)
 * @method SetList|null findOneBy(array $criteria, array $orderBy = null)
 * @method SetList[]    findAll()
 * @method SetList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SetListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetList::class);
    }

    /**
     * Get a SetList with all its child lists and their models
     */
    public function getModelListChildrenById(string $id): array
    {
        return $this->findBy(['parentList' => $id]);
    }

    /**
     * Find child boards of a parent that match a search query.
     *
     * @return SetList[]
     */
    public function findChildrenByQuery(string $parentId, string $query): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('sl')
            ->where('sl.parentList = :parentId')
            ->andWhere('LOWER(sl.title) LIKE :q OR LOWER(sl.description) LIKE :q')
            ->setParameter('parentId', $parentId)
            ->setParameter('q', $q)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find public boards excluding a specific user's boards.
     *
     * @return SetList[]
     */
    public function findPublicExcludingUser(string $excludeUserDataId, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('sl')
            ->where('sl.public = true')
            ->andWhere('sl.userData != :excludeId')
            ->setParameter('excludeId', $excludeUserDataId)
            ->orderBy('sl.publicationDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search public boards by title, description, or contained set name/number.
     *
     * @return SetList[]
     */
    public function searchPublic(string $query, int $limit, int $offset, ?string $excludeUserDataId = null): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        $qb = $this->createQueryBuilder('sl')
            ->leftJoin('sl.setLinks', 'sls')
            ->leftJoin('sls.set', 's')
            ->leftJoin('s.theme', 't')
            ->where('sl.public = true')
            ->andWhere(
                'LOWER(sl.title) LIKE :q
                 OR LOWER(sl.description) LIKE :q
                 OR LOWER(s.name) LIKE :q
                 OR LOWER(s.baseNumber) LIKE :q
                 OR LOWER(s.number) LIKE :q
                 OR LOWER(t.name) LIKE :q'
            )
            ->setParameter('q', $q);

        if (is_numeric($query) && (int) $query > 0) {
            $qb->orWhere('sl.public = true AND s.year = :year')
               ->setParameter('year', (int) $query);
        }

        if ($excludeUserDataId !== null) {
            $qb->andWhere('sl.userData != :excludeId')
               ->setParameter('excludeId', $excludeUserDataId);
        }

        return $qb
            ->orderBy('sl.publicationDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->distinct()
            ->getQuery()
            ->getResult();
    }

}
