<?php
namespace App\Entity\Lego;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Controller\Lego\DeleteSetListController;
use App\Controller\Lego\SetListController;
use App\Dto\Request\Lego\CreateSetRequest;
use App\Entity\User\UserData;
use App\Repository\Lego\SetListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Lego\Set as LegoModel;

/** A model list. */
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: SetListRepository::class)]
#[ApiResource(
    shortName: 'Lego set list',
    description: 'List for lego models',
    operations: [
        new Post(
            uriTemplate: '/set-list',
            types: ['https://schema.org/MediaObject'],
            outputFormats: ['jsonld' => ['application/ld+json']],
            controller: SetListController::class,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'string'
                                    ],
                                    'title' => [
                                        'type' => 'string'
                                    ],
                                    'description' => [
                                        'type' => 'string'
                                    ],
                                    'publicPrivate' => [
                                        'type' => 'bool'
                                    ],
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ],
                                'required' => ['title'],
                            ]
                        ]
                    ])
                )
            ),
            normalizationContext: ['groups' => ['modelList:read']],
            deserialize: false,
        ),
        new Delete(
            uriTemplate: '/set-list/delete/{id}',
            controller: DeleteSetListController::class,
            security: "is_granted('ROLE_ADMIN') or user == object.getUserData()->getOwner()"
        ),
        new Get(
            uriTemplate: '/set-list/get/{id}',
            controller: SetListController::class.'::getModelListById',
        ),
        new GetCollection(
            uriTemplate: '/set-lists',
            controller: SetListController::class.'::getSetListsByUser',
        ),
        new GetCollection(
            uriTemplate: '/set-lists/{id}',
            controller: SetListController::class.'::getSetListChildrenAndSets',
        )
    ],
    normalizationContext: ['groups' => ['modelList:read']],
    denormalizationContext: ['groups' => ['modelList:write']],
)]
#[ORM\Table(name: 'lego_set_list')]
class SetList
{
    /** The ID lego modal list */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    #[Groups(['lego_set:read','lego_set:write'])]
    private ?UuidInterface $id = null;

    /** The title of this list. */
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['modelList:read'])]
    public string $title = '';

    /** The description of this list. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Blank]
    #[Groups(['modelList:read'])]
    public ?string $description = '';

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[Assert\NotNull]
    #[Groups(['modelList:read'])]
    public bool $public = true;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['modelList:read'])]
    public ?string $contentUrl = null;

    #[Vich\UploadableField(
        mapping: 'media_object_lego',
        fileNameProperty: 'filePath',
    )]
    #[Groups(['modelList:write'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    /** The publication date of this model list. */
    #[ORM\Column]
    #[Assert\NotNull]
    public ?\DateTimeImmutable $publicationDate = null;

    #[ORM\OneToMany(
        targetEntity: SetListSet::class,
        mappedBy: 'setList',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $setLinks;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childLists')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?self $parentList = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentList', cascade: ['persist', 'remove'])]
    private iterable $childLists;

    #[ORM\ManyToOne(targetEntity: UserData::class, inversedBy: 'modelLists')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")] // Prevents null values, remove if not needed
    private ?UserData $userData = null;


    public function __construct()
    {
        $this->childLists = new ArrayCollection();
        $this->setLinks = new ArrayCollection();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getParentList(): ?self
    {
        return $this->parentList;
    }

    public function setParentList(?self $parentList): void
    {
        $this->parentList = $parentList;
    }

    public function getChildLists(): iterable
    {
        return $this->childLists;
    }

    public function setChildLists(iterable $childLists): void
    {
        $this->childLists = $childLists;
    }

    public function addChildList(self $childList): void
    {
        if (!$this->childLists->contains($childList)) {
            $this->childLists[] = $childList;
            $childList->setParentList($this);
        }
    }

    public function removeChildList(self $childList): void
    {
        if ($this->childLists->contains($childList)) {
            $this->childLists->removeElement($childList);
            $childList->setParentList(null);
        }
    }

    public function getSetLinks(): Collection
    {
        return $this->setLinks;
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
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeImmutable $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    public function getUserData(): ?UserData
    {
        return $this->userData;
    }

    public function setUserData(?UserData $userData): self
    {
        $this->userData = $userData;
        return $this;
    }

}
