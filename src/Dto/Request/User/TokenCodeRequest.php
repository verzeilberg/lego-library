<?php
namespace App\Dto\Request\User;

use App\Constant\JwtActions;
use App\Validator\JwtToken;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Activate Account Request DTO
 */
class TokenCodeRequest
{
    #[Assert\NotBlank(message:"Token can not be blank.")]
    #[Assert\Length(max: 2555)]
    #[Groups(['user:update'])]
    public string $token;

    #[Assert\NotBlank(message:"Code can not be blank.")]
    #[Assert\Length(min: 4, max: 4)]
    #[Groups(['user:update'])]
    public int $code;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }




}
