<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $token;

    #[ORM\Column(type: 'integer', length: 4, unique: false)]
    private $code;

    #[ORM\Column(type: 'datetime')]
    private $expiresAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = Uuid::v4()->toRfc4122(); // Use UUID for token generation
        $this->code = rand(1000, 9999);
        $this->expiresAt = new \DateTime('+1 hour'); // Token expires in 1 hour
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    public function isExpired(): bool
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }


}
