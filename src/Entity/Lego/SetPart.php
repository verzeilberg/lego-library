<?php

namespace App\Entity\Lego;

use App\Repository\Lego\SetPartRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SetPartRepository::class)]
#[ORM\Table(
    name: 'lego_set_part',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_set_part_color', columns: ['model_number','part_color_id'])]
)]
class SetPart
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    #[Groups(['lego_set:read'])]
    private ?UuidInterface $id = null;

    #[Groups(['lego_set:read'])]
    #[ORM\ManyToOne(targetEntity: Set::class, inversedBy: 'setParts')]
    #[ORM\JoinColumn(
        name: 'model_number',
        referencedColumnName: 'number',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private Set $model;

    #[Groups(['lego_set:read'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'part_color_id', nullable: false, onDelete: 'CASCADE')]
    private PartColor $partColor;

    #[Groups(['lego_set:read'])]
    #[ORM\Column(type: 'integer')]
    private int $quantity;

    // === Convenience ===
    public function getPart(): Part { return $this->partColor->getPart(); }
    public function getColor(): Color { return $this->partColor->getColor(); }

    // === Getters / Setters ===
    public function getId(): ?UuidInterface { return $this->id; }
    public function getModel(): Set { return $this->model; }
    public function setModel(Set $model): self { $this->model = $model; return $this; }
    public function getPartColor(): PartColor { return $this->partColor; }
    public function setPartColor(PartColor $partColor): self { $this->partColor = $partColor; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
}
