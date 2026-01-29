<?php

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\OpenApi\Model;
use App\Controller\User\DeleteUser;
use App\Controller\User\ReadUser;
use App\Controller\User\UpdateUser;
use App\Dto\Request\User\ProfileRequest;
use App\Entity\Lego\SetList;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use ApiPlatform\Metadata\ApiProperty;

#[Vich\Uploadable]
#[ORM\Entity]
#[ApiResource(
    shortName: 'User data',
    description: 'Set UserData',
    operations: [
        new GetCollection(),
        new Get(
            uriTemplate: '/user-data/',
            defaults: ['dto' => ProfileRequest::class],
            controller: ReadUser::class,
            security: 'is_granted("ROLE_USER") and object.getOwner() == user',
            output: ProfileRequest::class
        ),
        // POST for creating / file upload
        new Post(
            defaults: ['dto' => ProfileRequest::class],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'userName' => ['type' => 'string'],
                                    'firstName' => ['type' => 'string'],
                                    'lastName' => ['type' => 'string'],
                                    'email' => ['type' => 'string', 'format' => 'email'],
                                    'file' => ['type' => 'string', 'format' => 'binary']
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
        new Post(
            uriTemplate: '/user-data/edit',
            types: ['https://schema.org/MediaObject'],
            outputFormats: ['jsonld' => ['application/ld+json']],
            controller: UpdateUser::class,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'userName'  => ['type' => 'string'],
                                    'firstName' => ['type' => 'string'],
                                    'lastName'  => ['type' => 'string'],
                                    'bio'       => ['type' => 'string'],
                                    'file'      => [
                                                    'type'      => 'string',
                                                    'format'    => 'binary'
                                    ]
                                ],
                            ]
                        ]
                    ])
                )
            ),
            normalizationContext: ['groups' => ['userData:read']],
            deserialize: false,
        ),
        new Delete(
            uriTemplate: '/user-data/delete',
            controller: DeleteUser::class,
            security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"
        ),
        new Put(security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"),
    ],
    normalizationContext: ['groups' => ['userData:read']],
    denormalizationContext: ['groups' => ['userData:update']]
)]
class UserData
{
    #[Groups(['userData:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['userData:read', 'userData:update'])]
    private ?string $userName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['userData:read', 'userData:update'])]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['userData:read', 'userData:update'])]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    #[Groups(['userData:read', 'userData:update'])]
    private ?string $bio = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'userData', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['userData:read'])]
    public ?string $contentUrl = null;

    #[Vich\UploadableField(mapping: 'media_object_profile_picture', fileNameProperty: 'filePath')]
    #[Groups(['userData:update'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'userData', targetEntity: SetList::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $modelLists;

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

    public function addModelList(SetList $modelList): void
    {
        if (!$this->modelLists->contains($modelList)) {
            $this->modelLists[] = $modelList;
            $modelList->setUserData($this); // Ensure bidirectional relationship
        }
    }

    public function getModelLists(): iterable
    {
        return $this->modelLists;
    }

    public function setModelLists(iterable $modelLists): void
    {
        $this->modelLists = $modelLists;
    }

    public function getBio(): null|string
    {
        return $this->bio;
    }

    public function setBio(string $bio): void
    {
        $this->bio = $bio;
    }

    public function removeFile(): void
    {
        $this->file = null; // signals to Vich "remove file"
        $this->updatedAt = new \DateTimeImmutable();
    }
}
