<?php

namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PatchRequest
{
    #[Assert\Callback]
    public function validateAtLeastOneField(ExecutionContextInterface $context): void
    {
        if (
            empty($this->firstName) &&
            empty($this->lastName) &&
            empty($this->userName) &&
            empty($this->plainPassword)
        ) {
            $context->buildViolation('At least one of the fields must be filled.')
                ->addViolation();
        }
    }

    #[Assert\Length(max: 255)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    public string $firstName;

    #[Assert\Length(max: 255)]
    #[Groups(['user:create', 'user:update'])]
    public string $lastName;


    #[Assert\Length(max: 255)]
    #[Groups(['user:update'])]
    public string $userName;

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
