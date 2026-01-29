<?php

namespace App\Entity\Lego;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lego_user_set_part')]
class UserSetPart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'partStates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserSet $userSet;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SetPart $setPart;

    #[ORM\Column(type: 'integer')]
    private int $missingQuantity = 0;

    #[ORM\Column(type: 'integer')]
    private int $damagedQuantity = 0;

    // === Getters ===

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserSet(): UserSet
    {
        return $this->userSet;
    }

    public function getSetPart(): SetPart
    {
        return $this->setPart;
    }

    public function getMissingQuantity(): int
    {
        return $this->missingQuantity;
    }

    public function getDamagedQuantity(): int
    {
        return $this->damagedQuantity;
    }

    // === Setters ===

    public function setUserSet(UserSet $userSet): self
    {
        $this->userSet = $userSet;
        return $this;
    }

    public function setSetPart(SetPart $setPart): self
    {
        $this->setPart = $setPart;
        return $this;
    }

    public function setMissingQuantity(int $missingQuantity): self
    {
        $this->missingQuantity = max(0, $missingQuantity);
        return $this;
    }

    public function setDamagedQuantity(int $damagedQuantity): self
    {
        $this->damagedQuantity = max(0, $damagedQuantity);
        return $this;
    }

    // === Derived Convenience Methods ===

    public function getRequiredQuantity(): int
    {
        return $this->setPart->getQuantity();
    }

    public function getOwnedQuantity(): int
    {
        return max(
            0,
            $this->getRequiredQuantity()
            - $this->missingQuantity
            - $this->damagedQuantity
        );
    }

    public function isComplete(): bool
    {
        return $this->getOwnedQuantity() >= $this->getRequiredQuantity();
    }

    public function getPartColor(): PartColor
    {
        return $this->setPart->getPartColor();
    }

    public function getPart(): Part
    {
        return $this->setPart->getPartColor()->getPart();
    }

    public function getColor(): Color
    {
        return $this->setPart->getPartColor()->getColor();
    }
}
