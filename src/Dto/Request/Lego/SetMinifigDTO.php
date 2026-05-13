<?php

namespace App\Dto\Request\Lego;
class SetMinifigDTO
{
    public int $id;
    public string $setNumId;
    public string $name;
    public ?string $imageUrl;

    public ?int $quantity;
}
