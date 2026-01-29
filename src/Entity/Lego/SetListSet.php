<?php

namespace App\Entity\Lego;

use App\Entity\Media\MediaObject;
use App\Repository\Lego\SetListSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Represents the association between a LEGO Set and a SetList.
 *
 * This join entity allows additional configuration for a Set
 * within a specific list, such as display preferences and
 * attached media assets.
 *
 * Uniqueness is enforced on (set_number, set_list_id) to
 * prevent duplicate entries in a list.
 */
#[ORM\Entity(repositoryClass: SetListSetRepository::class)]
#[ORM\Table(
    name: 'lego_set_list_set',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_set_list_set',
            columns: ['set_number', 'set_list_id']
        )
    ]
)]
class SetListSet
{
    /**
     * Primary identifier.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lego_set:read','lego_set:write'])]
    private ?int $id = null;

    /**
     * LEGO set referenced by this list entry.
     *
     * Owning side of the relation.
     */
    #[ORM\ManyToOne(targetEntity: Set::class, inversedBy: 'listLinks')]
    #[ORM\JoinColumn(
        name: 'set_number',
        referencedColumnName: 'number',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private Set $set;

    /**
     * Parent SetList containing this entry.
     *
     * Owning side of the relation.
     */
    #[ORM\ManyToOne(targetEntity: SetList::class, inversedBy: 'setLinks')]
    #[ORM\JoinColumn(
        name: 'set_list_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private SetList $setList;

    /**
     * Controls whether images for this set are rendered
     * inside the list UI.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $showImages = true;

    /**
     * Controls whether parts for this set are rendered
     * inside the list UI.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $showParts = true;

    /**
     * Media objects attached to this set inside the list.
     *
     * Cascade:
     *  - persist: automatically saved
     *  - remove: deleted when this entity is removed
     *
     * orphanRemoval:
     *  - ensures detached MediaObjects are deleted
     *
     * @var Collection<int, MediaObject>
     */
    #[ORM\OneToMany(
        targetEntity: MediaObject::class,
        mappedBy: 'setListSet',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $mediaObjects;

    /**
     * Initializes Doctrine collections.
     */
    public function __construct()
    {
        $this->mediaObjects = new ArrayCollection();
    }

    /**
     * Returns the primary identifier.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the LEGO Set associated with this list entry.
     */
    public function getSet(): Set
    {
        return $this->set;
    }

    /**
     * Assigns the LEGO Set for this list entry.
     */
    public function setSet(Set $set): static
    {
        $this->set = $set;

        return $this;
    }

    /**
     * Returns the parent SetList.
     */
    public function getSetList(): SetList
    {
        return $this->setList;
    }

    /**
     * Assigns the parent SetList.
     */
    public function setSetList(SetList $setList): static
    {
        $this->setList = $setList;

        return $this;
    }

    /**
     * Whether images should be displayed.
     */
    public function isShowImages(): bool
    {
        return $this->showImages;
    }

    /**
     * Enables or disables image display.
     */
    public function setShowImages(bool $showImages): static
    {
        $this->showImages = $showImages;

        return $this;
    }

    /**
     * Whether parts should be displayed.
     */
    public function isShowParts(): bool
    {
        return $this->showParts;
    }

    /**
     * Enables or disables part display.
     */
    public function setShowParts(bool $showParts): static
    {
        $this->showParts = $showParts;

        return $this;
    }

    /**
     * Returns attached media objects.
     *
     * @return Collection<int, MediaObject>
     */
    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    /**
     * Attaches a MediaObject and synchronizes the
     * owning side of the relationship.
     */
    public function addMediaObject(MediaObject $media): static
    {
        if (!$this->mediaObjects->contains($media)) {
            $this->mediaObjects->add($media);
            $media->setSetListSet($this);
        }

        return $this;
    }

    /**
     * Detaches a MediaObject and clears the owning side
     * if still pointing to this entity.
     */
    public function removeMediaObject(MediaObject $media): static
    {
        if ($this->mediaObjects->removeElement($media)) {
            if ($media->getSetListSet() === $this) {
                $media->setSetListSet(null);
            }
        }

        return $this;
    }
}
