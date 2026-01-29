<?php

namespace App\Entity\Lego;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ColorRepository::class)]
#[ORM\Table(name: 'lego_color')]
class Color
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[Groups(['color:read','lego_set:read'])]
    private int $id;

    #[ORM\Column(length: 50)]
    #[Groups(['color:read','lego_set:read'])]
    private string $name;

    #[ORM\Column(length: 6)]
    #[Groups(['color:read','lego_set:read'])]
    private string $rgb;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['color:read','lego_set:read'])]
    private bool $isTrans = false;

    // === Getters / Setters ===
    public function getId(): int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getRgb(): string { return $this->rgb; }
    public function setRgb(string $rgb): self { $this->rgb = strtoupper($rgb); return $this; }

    public function isTrans(): bool { return $this->isTrans; }
    public function setIsTrans(bool $isTrans): self { $this->isTrans = $isTrans; return $this; }

    public function getHexColor(): string { return '#' . $this->rgb; }
}
