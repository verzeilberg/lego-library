<?php

namespace App\Dto\Request\Lego;
class SetMinifigDTO
{
    public int $id;
    public int $setMinifigId;
    public string $setNumId;
    public string $name;
    public ?string $imageUrl;

    public ?int $quantity;
    public int $missingQuantity = 0;
}
