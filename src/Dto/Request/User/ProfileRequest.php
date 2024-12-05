<?php
namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\User;
use App\Validator\UniqueEntityDto;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

#[ApiResource]
class ProfileRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['userData:read', 'userData:create', 'userData:update'])]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['userData:read', 'userData:create', 'userData:update'])]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 1024)]
    #[Groups(['userData:read', 'userData:create', 'userData:update'])]
    public string $email;

    #[Assert\Blank]
    #[Assert\Length(max: 1024)]
    #[Groups(['userData:read', 'userData:create', 'userData:update'])]
    public string $userName;

    #[Assert\NotBlank(message:"Profile picture can not be blank.")]
    #[ApiProperty(types: ['http://schema.org/MediaObject'])]
    #[File(maxSize: "1024k", mimeTypes: ["image/png", "image/jpeg"], mimeTypesMessage:"Alleen PDF bestanden zijn toegestaan." )]
    #[Groups(['userData:read', 'userData:create', 'userData:update'])]
    public string $profilePicture;


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

    public function getProfilePicture(): string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(string|null $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }



}
