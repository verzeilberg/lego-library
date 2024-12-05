<?php
namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
class ResetPasswordRequest
{

    #[Assert\NotBlank(message:"Token can not be blank.")]
    #[Assert\Length(max: 255)]
    #[Groups(['user:update'])]
    public string $token;

    #[Assert\NotBlank(message:"Code can not be blank.")]
    #[Assert\Length(min: 4, max: 4)]
    #[Groups(['user:update'])]
    public string $code;

    #[Assert\NotBlank(message:"Password can not be blank.")]
    #[Assert\Length(min: 8, max: 24, minMessage: "Password must be at least {{ limit }} characters long.", maxMessage: "Password can be max {{ limit }} characters long.")]
    #[Groups(['user:update'])]
    public string $plainPassword;

    #[Assert\NotBlank(message:"Verify password can not be blank.")]
    #[Groups(['user:update'])]
    public string $verifyPlainPassword;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getVerifyPlainPassword(): string
    {
        return $this->verifyPlainPassword;
    }

    public function setVerifyPlainPassword(string $verifyPlainPassword): void
    {
        $this->verifyPlainPassword = $verifyPlainPassword;
    }
}
