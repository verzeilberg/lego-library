<?php

namespace App\Dto\Request\Lego;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateDefectPartsRequest
{
    #[Assert\NotBlank]
    #[Groups(['user:create'])]
    public string $setNumber;

    #[Assert\NotBlank]
    #[Groups(['user:create'])]
    public string $bordId;

    #[Assert\NotBlank]
    #[Groups(['user:create'])]
    public string $partId;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Groups(['user:create'])]
    public int $colorId;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(['user:create'])]
    public int $missingQuantity = 0;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(['user:create'])]
    public int $damagedQuantity = 0;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(['user:create'])]
    public int $discolouredQuantity = 0;

    public function getSetNumber(): string { return $this->setNumber; }
    public function setSetNumber(string $setNumber): self { $this->setNumber = $setNumber; return $this; }

    public function getBordId(): string { return $this->bordId; }
    public function setBordId(string $bordId): self { $this->bordId = $bordId; return $this; }

    public function getPartId(): string { return $this->partId; }
    public function setPartId(string $partId): self { $this->partId = $partId; return $this; }

    public function getColorId(): int { return $this->colorId; }
    public function setColorId(int $colorId): self { $this->colorId = $colorId; return $this; }

    public function getMissingQuantity(): int { return $this->missingQuantity; }
    public function setMissingQuantity(int $missingQuantity): self { $this->missingQuantity = $missingQuantity; return $this; }

    public function getDamagedQuantity(): int { return $this->damagedQuantity; }
    public function setDamagedQuantity(int $damagedQuantity): self { $this->damagedQuantity = $damagedQuantity; return $this; }

    public function getDiscolouredQuantity(): int { return $this->discolouredQuantity; }
    public function setDiscolouredQuantity(int $discolouredQuantity): self { $this->discolouredQuantity = $discolouredQuantity; return $this; }
}
