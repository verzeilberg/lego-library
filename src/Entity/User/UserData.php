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
use App\Entity\Lego\SetRating;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\ApiProperty;

#[Vich\Uploadable]
#[ORM\Entity]
#[ApiResource(
    shortName: 'User data',
    description: 'User profile data',
    operations: [
        new GetCollection(),
        new Get(
            uriTemplate: '/user-data/',
            defaults: ['dto' => ProfileRequest::class],
            controller: ReadUser::class,
            security: 'is_granted("ROLE_USER") and object.getOwner() == user',
            output: ProfileRequest::class
        ),
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
            output: ProfileRequest::class
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
                                    'file'      => ['type' => 'string', 'format' => 'binary']
                                ],
                            ]
                        ]
                    ])
                )
            ),
            normalizationContext: ['groups' => ['userData:read']],
            deserialize: false
        ),
        new Delete(
            uriTemplate: '/user-data/delete',
            controller: DeleteUser::class,
            security: "is_granted('ROLE_ADMIN') or user == object.getOwner()"
        ),
        new Put(security: "is_granted('ROLE_ADMIN') or user == object.getOwner()")
    ],
    normalizationContext: ['groups' => ['userData:read']],
    denormalizationContext: ['groups' => ['userData:update']]
)]
class UserData
{
    // ======================
    // Properties
    // ======================

    #[Groups(['userData:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
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
    private ?string $contentUrl = null;

    #[Vich\UploadableField(mapping: 'media_object_profile_picture', fileNameProperty: 'filePath')]
    #[Groups(['userData:update'])]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: SetRating::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $setRatings;

    #[ORM\OneToMany(mappedBy: 'userData', targetEntity: SetList::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $modelLists;

    // ======================
    // Constructor
    // ======================

    public function __construct()
    {
        $this->setRatings = new ArrayCollection();
        $this->modelLists = new ArrayCollection();
    }

    // ======================
    // Getters / Setters
    // ======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        if ($this->owner === $owner) {
            return $this; // already set, prevent recursion
        }

        $this->owner = $owner;

        // only set userData if not already set
        if ($owner !== null && $owner->getUserData() !== $this) {
            $owner->setUserData($this);
        }

        return $this;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): self
    {
        $this->contentUrl = $contentUrl;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, SetRating>
     */
    public function getSetRatings(): Collection
    {
        return $this->setRatings;
    }

    public function addSetRating(SetRating $rating): self
    {
        if (!$this->setRatings->contains($rating)) {
            $this->setRatings->add($rating);
            $rating->setUser($this);
        }
        return $this;
    }

    public function removeSetRating(SetRating $rating): self
    {
        if ($this->setRatings->removeElement($rating) && $rating->getUser() === $this) {
            $rating->setUser(null);
        }
        return $this;
    }

    /**
     * @return Collection<int, SetList>
     */
    public function getModelLists(): Collection
    {
        return $this->modelLists;
    }

    public function addModelList(SetList $list): self
    {
        if (!$this->modelLists->contains($list)) {
            $this->modelLists->add($list);
            $list->setUserData($this);
        }
        return $this;
    }

    public function removeModelList(SetList $list): self
    {
        if ($this->modelLists->removeElement($list)) {
            $list->setUserData(null);
        }
        return $this;
    }

    public function removeFile(): self
    {
        $this->file = null;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
