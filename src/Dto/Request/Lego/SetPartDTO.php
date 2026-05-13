<?php

namespace App\Dto\Request\Lego;
class SetPartDTO
{
    public string $setPartId;
    public string $setNumber;
    public string $partNumber;
    public string $name;

    public int $quantity;

    public int $missingQuantity = 0;
    public int $damagedQuantity = 0;
    public int $discolouredQuantity = 0;

    public int $colorId;
    public string $colorName;
    public string $colorHex;
    public bool $isTransparent;

    public ?string $imageUrl;
}
