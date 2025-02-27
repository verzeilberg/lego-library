<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\User\Profile;
use App\Dto\Request\User\ProfileRequest;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\OpenApi\Model;


#[Vich\Uploadable]
#[ORM\Entity]
#[ApiResource(
    shortName: 'User data',
    description: 'Model UserData',
    operations: [
        new GetCollection(),
        new Get(
            uriTemplate: '/user-data/',
            defaults: [
                'dto' => ProfileRequest::class
            ],
            controller: Profile::class,
            security: 'is_granted("ROLE_USER") and object.getOwner() == user',
            output: ProfileRequest::class,
        ),
        new Post(
            defaults: [
                'dto' => ProfileRequest::class
            ],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'userName' => [
                                        'type' => 'string'
                                    ],
                                    'firstName' => [
                                        'type' => 'string'
                                    ],
                                    'lastName' => [
                                        'type' => 'string'
                                    ],
                                    'email' => [
                                        'type' => 'email'
                                    ],
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ],
                                'required' => ['firstName', 'lastName', 'email'],
                            ]
                        ]
                    ])
                )
            ),
            security: "is_granted('ROLE_ADMIN') or user == object.getOwner()",
            input: ProfileRequest::class,
            output: ProfileRequest::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"),
        new Put(security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"),
        new Patch(security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"),
    ],
    normalizationContext: ['groups' => ['userData:read', 'userData:create', 'userData:update']],
    denormalizationContext: ['groups' => ['userData:read', 'userData:create', 'userData:update']]
)]
class UserData
{
    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private string $userName;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private string $firstName;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private string $lastName;


    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'userData', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['userData:read'])]
    public ?string $contentUrl = null;

    #[Vich\UploadableField(
        mapping: 'media_object',
        fileNameProperty: 'filePath',
    )]
    #[Groups(['userData:write'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        if ($owner !== null && $owner->getUserData() !== $this) {
            $owner->getUserData($this);
        }

        $this->owner = $owner;

        return $this;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): void
    {
        $this->contentUrl = $contentUrl;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;

        // Force Doctrine to update the entity when a new file is uploaded
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
