<?php

namespace App\Entity\Lego;

use App\Repository\Lego\MiniFigRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Represents a LEGO minifigure.
 */
#[ORM\Entity(repositoryClass: MiniFigRepository::class)]
#[ORM\Table(name: 'lego_minifig')]
#[ApiResource(
    shortName: 'Minifigs',
    description: 'LEGO minifigs',
)]
class Minifig
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[Groups(['lego_minifig:read','lego_minifig:write'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['lego_minifig:read','lego_minifig:write'])]
    private string $setNumId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['lego_minifig:read','lego_minifig:write'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['lego_minifig:read','lego_minifig:write'])]
    private ?string $imageUrl = null;

    /**
     * @var Collection<int, SetMinifig>
     */
    #[ORM\OneToMany(mappedBy: 'minifig', targetEntity: SetMinifig::class, cascade: ['persist', 'remove'])]
    private Collection $setLinks;

    public function __construct()
    {
        $this->setLinks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $Id
     * @return Minifig
     */
    public function setId(int $id): Minifig
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSetNumId(): string
    {
        return $this->setNumId;
    }

    /**
     * @param string $setNumId
     * @return Minifig
     */
    public function setSetNumId(string $setNumId): Minifig
    {
        $this->setNumId = $setNumId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return Collection<int, SetMinifig>
     */
    public function getSetLinks(): Collection
    {
        return $this->setLinks;
    }

    public function addSetLink(SetMinifig $link): static
    {
        if (!$this->setLinks->contains($link)) {
            $this->setLinks->add($link);
            $link->setMinifig($this);
        }
        return $this;
    }

    public function removeSetLink(SetMinifig $link): static
    {
        if ($this->setLinks->removeElement($link)) {
            if ($link->getMinifig() === $this) {
                $link->setMinifig(null);
            }
        }
        return $this;
    }
}
