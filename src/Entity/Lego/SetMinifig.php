<?php

namespace App\Entity\Lego;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Join entity linking a LEGO set with a minifig and storing the quantity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'lego_set_minifig')]
#[ApiResource]
class SetMinifig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['lego_set:read', 'set_minifig:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Set::class, inversedBy: 'setMinifigs')]
    #[ORM\JoinColumn(
        name: 'set_number',
        referencedColumnName: 'number',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Set $set = null;

    #[ORM\ManyToOne(targetEntity: Minifig::class, inversedBy: 'setLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['lego_set:read', 'set_minifig:read'])]
    private ?Minifig $minifig = null;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['lego_set:read', 'set_minifig:read'])]
    private int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSet(): ?Set
    {
        return $this->set;
    }

    public function setSet(?Set $set): static
    {
        $this->set = $set;
        return $this;
    }

    public function getMinifig(): ?Minifig
    {
        return $this->minifig;
    }

    public function setMinifig(?Minifig $minifig): static
    {
        $this->minifig = $minifig;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }
}
