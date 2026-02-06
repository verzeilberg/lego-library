<?php

namespace App\Entity\Lego;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Controller\Lego\CreateSetController;
use App\Controller\Lego\DeleteSetFromSetListController;
use App\Controller\Lego\GetSetController;
use App\Controller\Lego\UploadSetImagesController;
use App\Dto\Request\Lego\CreateSetRequest;
use App\Entity\Media\MediaObject;
use App\Repository\Lego\SetRepository;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Represents a LEGO set domain entity.
 *
 * This entity is:
 *  - A persisted LEGO model record.
 *  - An ApiPlatform resource with custom controllers.
 *  - Uploadable through VichUploader.
 *  - Timestamped via TimestampableTrait.
 *
 * The primary identifier is the LEGO set number.
 */
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: SetRepository::class)]
#[ORM\Table(name: 'lego_set')]
#[ApiResource(
    shortName: 'Lego models',
    description: 'Lego models',
    operations: [
        new Get(
            uriTemplate: '/lego/set-lists/{listId}/sets/{number}',
            controller: GetSetController::class . '::getSetById',
            shortName: 'Get lego set by list id and set id',
            read: true,
            deserialize: true
        ),
        new Get(
            uriTemplate: '/lego/sets/{number}/parts',
            controller: GetSetController::class . '::getPartsBySetId',
            shortName: 'Get lego parts by set number',
            read: false
        ),
        new Post(
            uriTemplate: '/lego/sets/create',
            formats: ['json' => ['application/json']],
            defaults: ['dto' => CreateSetRequest::class],
            controller: CreateSetController::class,
            shortName: 'Create lego set',
            input: CreateSetRequest::class,
            output: CreateSetRequest::class,
            deserialize: true,
        ),
        new Post(
            uriTemplate: '/lego/set-lists/{listId}/sets/{number}/add-images',
            controller: UploadSetImagesController::class,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'files[]' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                            'format' => 'binary'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            ),
            shortName: 'Add images to lego set',
            deserialize: false
        ),
        new Delete(
            uriTemplate: '/lego/list/{bordid}/set/{setnr}',
            controller: DeleteSetFromSetListController::class,
            shortName: 'Delete lego set from lego list',
            deserialize: true
        ),
    ],
    normalizationContext: ['groups' => ['lego_set:read', 'set_minifig:read']],
)]
class Set
{
    use TimestampableTrait;

    /**
     * LEGO set number.
     *
     * Acts as the primary key and public API identifier.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['lego_set:read','lego_set:write'])]
    private string $number;

    /**
     * Base set number (variant grouping).
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    #[Groups(['lego_set:read','lego_set:write'])]
    private ?string $baseNumber = null;

    /**
     * Marketing/display name of the set.
     */
    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    #[Groups(['lego_set:read','lego_set:write'])]
    private string $name = '';

    /**
     * Release year.
     */
    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank]
    #[Groups(['lego_set:read','lego_set:write'])]
    private int $year;

    /**
     * Number of physical parts in the set.
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Groups(['lego_set:read','lego_set:write'])]
    private int $numParts;

    /**
     * Aggregate user rating (0–5).
     */
    #[ORM\Column(type: 'decimal', precision: 2, scale: 1)]
    #[Assert\Range(min: 0, max: 5)]
    #[Groups(['lego_set:read'])]
    private float $rating = 0.0;

    /**
     * Public content URL for primary image.
     */
    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['lego_set:write'])]
    private ?string $contentUrl = null;

    /**
     * Uploaded a file for VichUploader processing.
     *
     * Not persisted directly—mapped to filePath.
     */
    #[Vich\UploadableField(mapping: 'media_object_lego', fileNameProperty: 'filePath')]
    #[Groups(['lego_set:write'])]
    private ?File $file = null;

    /**
     * Stored file name/path after upload.
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['lego_set:read'])]
    private ?string $filePath = null;


    /**
     * @var Collection
     */
    #[ORM\OneToMany(
        targetEntity: SetRating::class,
        mappedBy: 'set',
        orphanRemoval: true
    )]
    private Collection $ratings;

    /**
     * Parts belonging to this set.
     *
     * @var Collection<int, SetPart>
     */
    #[ORM\OneToMany(
        targetEntity: SetPart::class,
        mappedBy: 'model',
        cascade: ['persist', 'remove']
    )]
    #[Groups(['lego_set:read'])]
    private Collection $setParts;

    /**
     * @var Collection<int, SetMinifig>
     */
    #[ORM\OneToMany(mappedBy: 'set', targetEntity: SetMinifig::class, cascade: ['persist', 'remove'])]
    #[Groups(['lego_set:read'])]
    private Collection $setMinifigs;

    /**
     * Links to SetList entities through SetListSet join table.
     *
     * @var Collection<int, SetListSet>
     */
    #[ORM\OneToMany(
        targetEntity: SetListSet::class,
        mappedBy: 'set',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $listLinks;

    /**
     * Theme classification.
     */
    #[ORM\ManyToOne(targetEntity: Theme::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Groups(['lego_set:read','lego_set:write'])]
    #[ApiProperty(readableLink: false)]
    private ?Theme $theme = null;

    /**
     * Computed image URLs exposed through the API.
     * Not persisted.
     */
    #[Groups(['lego_set:read'])]
    private array $images = [];

    /**
     * Show parts or not
     * @var bool
     */
    #[Groups(['lego_set:read'])]
    private bool $showParts;

    /**
     * Show minifigs or not
     * @var bool
     */
    #[Groups(['lego_set:read'])]
    private bool $showMinifigs;

    /**
     * @var bool
     */
    #[Groups(['lego_set:read'])]
    private int $personalRating;

    /**
     * Initializes Doctrine collections.
     */
    public function __construct()
    {
        $this->ratings = new ArrayCollection();
        $this->setParts = new ArrayCollection();
        $this->setMinifigs = new ArrayCollection();
        $this->listLinks = new ArrayCollection();
    }

    // ======================
    // Identifiers
    // ======================

    /**
     * Returns the primary identifier.
     */
    public function getId(): string
    {
        return $this->baseNumber;
    }

    /**
     * Assigns the primary identifier.
     */
    public function setId(string $number): static
    {
        $this->baseNumber = $number;

        return $this;
    }

    /**
     * Returns the LEGO set number.
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Sets the LEGO set number.
     */
    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Returns the base number.
     */
    public function getBaseNumber(): ?string
    {
        return $this->baseNumber;
    }

    /**
     * Sets the base number.
     */
    public function setBaseNumber(?string $baseNumber): static
    {
        $this->baseNumber = $baseNumber;

        return $this;
    }

    /**
     * Returns the display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the display name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the release year.
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Sets the release year.
     */
    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return Collection<int, SetRating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(SetRating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setSet($this);
        }

        return $this;
    }

    public function removeRating(SetRating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            if ($rating->getSet() === $this) {
                $rating->setSet(null);
            }
        }

        return $this;
    }


    /**
     * Returns number of parts.
     */
    public function getNumParts(): int
    {
        return $this->numParts;
    }

    /**
     * Sets number of parts.
     */
    public function setNumParts(int $numParts): static
    {
        $this->numParts = $numParts;

        return $this;
    }

    /**
     * @return float
     */
    public function getRating(): float
    {
        return $this->rating;
    }

    /**
     * @param float $rating
     * @return Set
     */
    public function setRating(float $rating): Set
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * Returns content URL.
     */
    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    /**
     * Sets content URL.
     */
    public function setContentUrl(?string $contentUrl): static
    {
        $this->contentUrl = $contentUrl;

        return $this;
    }

    /**
     * Returns upload file for Vich.
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Assigns upload file and updates timestamps.
     */
    public function setFile(?File $file): static
    {
        $this->file = $file;

        if ($file !== null) {
            $this->setUpdatedAt(new \DateTimeImmutable());
        }

        return $this;
    }

    /**
     * Returns stored file path.
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Sets stored file path.
     */
    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    // ======================
    // Parts
    // ======================

    /**
     * Returns all parts in this set.
     *
     * @return Collection<int, SetPart>
     */
    public function getSetParts(): Collection
    {
        return $this->setParts;
    }

    /**
     * Adds a part and synchronizes owning side.
     */
    public function addSetPart(SetPart $setPart): static
    {
        if (!$this->setParts->contains($setPart)) {
            $this->setParts->add($setPart);
            $setPart->setModel($this);
        }

        return $this;
    }

    /**
     * Removes a part and clears owning side.
     */
    public function removeSetPart(SetPart $setPart): static
    {
        if ($this->setParts->removeElement($setPart)) {
            if ($setPart->getModel() === $this) {
                $setPart->setModel(null);
            }
        }

        return $this;
    }

    public function getSetMinifigs(): Collection
    {
        return $this->setMinifigs;
    }

    public function addSetMinifig(SetMinifig $link): static
    {
        if (!$this->setMinifigs->contains($link)) {
            $this->setMinifigs->add($link);
            $link->setSet($this);
        }
        return $this;
    }

    public function removeSetMinifig(SetMinifig $link): static
    {
        if ($this->setMinifigs->removeElement($link)) {
            if ($link->getSet() === $this) {
                $link->setSet(null);
            }
        }
        return $this;
    }

    // ======================
    // Theme
    // ======================

    /**
     * Returns theme entity.
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * Assigns theme entity.
     */
    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Returns a theme name for serialization.
     */
    #[Groups(['lego_set:read'])]
    public function getThemeName(): ?string
    {
        return $this->theme?->getName();
    }

    // ======================
    // Set Lists
    // ======================

    /**
     * Returns all list associations.
     *
     * @return Collection<int, SetListSet>
     */
    public function getListLinks(): Collection
    {
        return $this->listLinks;
    }

    /**
     * Adds a SetListSet join entity.
     */
    public function addListLink(SetListSet $link): static
    {
        if (!$this->listLinks->contains($link)) {
            $this->listLinks->add($link);
            $link->setSet($this);
        }

        return $this;
    }

    /**
     * Removes a SetListSet join entity.
     */
    public function removeListLink(SetListSet $link): static
    {
        if ($this->listLinks->removeElement($link)) {
            if ($link->getSet() === $this) {
                $link->setSet(null);
            }
        }

        return $this;
    }

    // ======================
    // Images
    // ======================

    /**
     * Returns computed image URLs.
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Sets computed image URLs.
     */
    public function setImages(array $images): static
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowParts(): bool
    {
        return $this->showParts;
    }

    /**
     * @param bool $showParts
     * @return Set
     */
    public function setShowParts(bool $showParts): Set
    {
        $this->showParts = $showParts;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowMinifigs(): bool
    {
        return $this->showMinifigs;
    }

    /**
     * @param bool $showMinifigs
     * @return Set
     */
    public function setShowMinifigs(bool $showMinifigs): Set
    {
        $this->showMinifigs = $showMinifigs;
        return $this;
    }

    /**
     * @return int
     */
    public function getPersonalRating(): int
    {
        return $this->personalRating;
    }

    /**
     * @param int $personalRating
     * @return Set
     */
    public function setPersonalRating(int $personalRating): Set
    {
        $this->personalRating = $personalRating;
        return $this;
    }




}
