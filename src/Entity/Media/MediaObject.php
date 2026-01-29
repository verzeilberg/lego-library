<?php

namespace App\Entity\Media;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetListSet;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Media object for Set images
 */
#[ORM\Entity]
#[Vich\Uploadable]
#[ApiResource(
    normalizationContext: ['groups' => ['media_object:read']],
    denormalizationContext: ['groups' => ['media_object:write']]
)]
class MediaObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['media_object:read'])]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'media_object_lego', fileNameProperty: 'filePath')]
    #[Groups(['media_object:write'])]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media_object:read', 'lego_set:read'])]
    private ?string $filePath = null;


    #[ORM\ManyToOne(targetEntity: SetListSet::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SetListSet $setListSet = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
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

    public function getSetListSet(): ?SetListSet
    {
        return $this->setListSet;
    }

    public function setSetListSet(?SetListSet $setListSet): void
    {
        $this->setListSet = $setListSet;
    }
}
