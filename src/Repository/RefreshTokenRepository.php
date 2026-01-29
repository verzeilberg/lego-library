<?php
// src/Repository/RefreshTokenRepository.php
namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 *
 * @method RefreshToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefreshToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefreshToken[]    findAll()
 * @method RefreshToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    // Optional: add custom queries if needed
    public function findValidToken(string $token): ?RefreshToken
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.token = :token')
            ->andWhere('r.expiresAt > :now')
            ->setParameters([
                'token' => $token,
                'now' => new \DateTime(),
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}

