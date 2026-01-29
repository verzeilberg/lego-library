<?php

namespace App\Entity\Lego;

use App\Repository\Lego\PartColorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PartColorRepository::class)]
#[ORM\Table(name: 'lego_part_color', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_part_color', columns: ['part_id','color_id'])])]
class PartColor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lego_set:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Part::class, inversedBy: 'colors')]
    #[ORM\JoinColumn(
        name: 'part_id',               // column in lego_part_color
        referencedColumnName: 'part_number', // column in lego_part
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['lego_set:read'])]
    private Part $part;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['lego_set:read'])]
    private Color $color;

    public function getId(): int { return $this->id; }
    public function getPart(): Part { return $this->part; }
    public function setPart(Part $part): self { $this->part = $part; return $this; }

    public function getColor(): Color { return $this->color; }
    public function setColor(Color $color): self { $this->color = $color; return $this; }
}
