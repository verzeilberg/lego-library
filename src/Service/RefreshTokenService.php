<?php

namespace App\Service;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class RefreshTokenService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $userId
     * @param \DateInterval|null $ttl
     * @return string
     * @throws \Exception
     */
    public function create(int $userId, \DateInterval $ttl = null): string
    {
        $token = new RefreshToken();
        $tokenString = Uuid::v4()->toRfc4122();
        $token->setToken($tokenString);
        $token->setUserId($userId);
        $token->setExpiresAt(new \DateTime('+' . ($ttl?->format('%s') ?? 30*24*60*60) . ' seconds'));

        $this->em->persist($token);
        $this->em->flush();

        return $tokenString;
    }

    /**
     * Validates a refresh token and returns the user ID if valid.
     *
     * @param string $token
     * @return int|null
     */
    public function validate(string $token): ?int
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        $refreshToken = $repo->find($token);

        if (!$refreshToken || $refreshToken->isExpired()) {
            return null;
        }

        return $refreshToken->getUserId();
    }

    /**
     * Revokes a refresh token.
     * @param string $token
     * @return void
     */
    public function revoke(string $token): void
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        $refreshToken = $repo->find($token);
        if ($refreshToken) {
            $this->em->remove($refreshToken);
            $this->em->flush();
        }
    }

    /**
     * Revokes all refresh tokens associated with a specific user.
     *
     * This method retrieves all refresh tokens tied to the provided user ID and removes them from the database.
     *
     * @param int $userId The unique identifier of the user whose tokens will be revoked.
     */
    public function revokeAllForUser(int $userId): void
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        $tokens = $repo->findBy(['userId' => $userId]);

        foreach ($tokens as $token) {
            $this->em->remove($token);
        }

        $this->em->flush();
    }
}
