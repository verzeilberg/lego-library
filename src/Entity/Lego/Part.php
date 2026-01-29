<?php

namespace App\Entity\Lego;

use App\Repository\Lego\PartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PartRepository::class)]
#[ORM\Table(name: 'lego_part')]
class Part
{
    #[ORM\Id]
    #[ORM\Column(name: 'part_number', length: 50)]
    #[Groups(['part:read', 'lego_set:read'])]
    private string $partNumber;

    #[ORM\Column(length: 100)]
    #[Groups(['part:read', 'lego_set:read'])]
    private string $name;

    // New property to store part image URL
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['part:read', 'lego_set:read'])]
    private ?string $imgUrl = null;

    #[ORM\OneToMany(targetEntity: PartColor::class, mappedBy: 'part', cascade: ['persist'])]
    private Collection $colors;

    public function __construct() { $this->colors = new ArrayCollection(); }

    // === Getters / Setters ===
    public function getPartNumber(): string { return $this->partNumber; }
    public function setPartNumber(string $partNumber): self { $this->partNumber = $partNumber; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getImgUrl(): ?string { return $this->imgUrl; }
    public function setImgUrl(?string $imgUrl): self { $this->imgUrl = $imgUrl; return $this; }

    public function getColors(): Collection { return $this->colors; }
    public function addColor(PartColor $color): self {
        if (!$this->colors->contains($color)) { $this->colors->add($color); $color->setPart($this); }
        return $this;
    }
    public function removeColor(PartColor $color): self { $this->colors->removeElement($color); return $this; }
}
