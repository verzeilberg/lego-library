<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Index(columns: ['token'], name: 'token_idx', options: ['lengths' => [255]])]
class UserToken
{

    CONST TYPE_USER_ACTIVATION  = 1;
    CONST TYPE_PASSWORD_RESET   = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'text')]
    private string $token;

    #[ORM\Column(type: 'integer', length: 4, unique: false)]
    private int $code;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer', length: 1, unique: false)]
    private int $type;

    public function __construct(User $user, $token, $code, $type, $expiresAt = null)
    {
        $this->user         = $user;
        $this->token        = $token;
        $this->code         = $code;
        $this->type         = $type;
        $this->expiresAt    = $expiresAt??new \DateTime('+1 hour'); // The Token expires in 1 hour
    }



    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getCode(): mixed
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

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }


}
