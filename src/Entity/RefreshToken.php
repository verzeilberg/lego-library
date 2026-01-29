<?php
// src/Entity/RefreshToken.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\RefreshTokenRepository;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
class RefreshToken
{
    #[ORM\Id, ORM\Column(type: 'string', length: 255)]
    private string $token;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): self { $this->token = $token; return $this; }

    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeInterface $expiresAt): self { $this->expiresAt = $expiresAt; return $this; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }

    public function isExpired(): bool { return $this->expiresAt < new \DateTime(); }
}
