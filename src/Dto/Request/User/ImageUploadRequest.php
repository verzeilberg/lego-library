<?php
namespace App\Dto\Request\User;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ImageUploadRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read'])]
    public string $uri;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read'])]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read'])]
    public string $type;

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }



}
