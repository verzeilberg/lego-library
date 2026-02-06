<?php
namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordRequest
{

    #[Assert\NotBlank(message:"Email can not be blank.")]
    #[Groups(['user:update'])]
    #[Assert\Email]
    public string $email;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


}
