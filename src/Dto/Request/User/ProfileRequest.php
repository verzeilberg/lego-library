<?php

namespace App\Dto\Request\User;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileRequest
{
    #[Groups(['userData:read', 'userData:update'])]
    public ?string $firstName = null;

    #[Groups(['userData:read', 'userData:update'])]
    public ?string $lastName = null;

    #[Groups(['userData:read', 'userData:update'])]
    public ?string $userName = null;

    #[Groups(['userData:read', 'userData:update'])]
    public ?string $bio = null;

    #[Assert\Email]
    #[Assert\Length(max: 1024)]
    #[Groups(['userData:update', 'userData:read', 'userData:create'])]
    public ?string $email = null;

    #[Groups(['userData:read', 'userData:update'])]
    public ?string $profilePicture = null;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
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

    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }

}
