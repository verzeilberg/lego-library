<?php

namespace App\Repository;

use App\Entity\User\Friendship;
use App\Entity\User\UserData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findFriends(UserData $userData): array
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :user OR f.recipient = :user) AND f.status = :status')
            ->setParameter('user', $userData)
            ->setParameter('status', 'accepted')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingReceived(UserData $userData): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.recipient = :user AND f.status = :status')
            ->setParameter('user', $userData)
            ->setParameter('status', 'pending')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getFriendUserDataList(UserData $userData): array
    {
        $friends = [];
        foreach ($this->findFriends($userData) as $friendship) {
            $friends[] = $friendship->getRequester()->getId() === $userData->getId()
                ? $friendship->getRecipient()
                : $friendship->getRequester();
        }
        return $friends;
    }

    public function getFriendPushTokens(UserData $userData): array
    {
        $tokens = [];
        foreach ($this->findFriends($userData) as $friendship) {
            $friend = $friendship->getRequester()->getId() === $userData->getId()
                ? $friendship->getRecipient()
                : $friendship->getRequester();
            if ($token = $friend->getPushToken()) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    public function findBetween(UserData $a, UserData $b): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where(
                '(f.requester = :a AND f.recipient = :b) OR (f.requester = :b AND f.recipient = :a)'
            )
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
