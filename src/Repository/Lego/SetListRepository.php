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

}
