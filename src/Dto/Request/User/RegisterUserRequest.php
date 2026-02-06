<?php
namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\User\User;
use App\Validator\UniqueEntityDto;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntityDto(field: 'email', entityClass: User::class, existsMessage: 'A user with this email address already exists')]
class RegisterUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:create', 'user:update'])]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 1024)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    public string $email;

    #[Assert\Blank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:update'])]
    public string $userName = '';

    #[Assert\NotBlank(message:"Password can not be blank.")]
    #[Assert\Length(min: 8, max: 24, minMessage: "Password must be at least {{ limit }} characters long.", maxMessage: "Password can be max {{ limit }} characters long.")]
    #[Groups(['user:create', 'user:update'])]
    public string $plainPassword;

    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }


}
