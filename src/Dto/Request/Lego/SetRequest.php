<?php

namespace App\Dto\Request\Lego;

use App\Entity\Lego\SetMinifig;
use App\Entity\Lego\SetPart;

class SetRequest
{
    public string $number;
    public ?string $baseNumber;
    public string $name;
    public int $year;
    public int $numParts;

    public int $specificParts;

    public int $totalQuantity;

    public int $totalMiniFigsParts;


    public float $rating;

    public ?string $themeName = null;

    /** @var array<array{id: int|null, path: string}> */
    public array $images = [];

    public bool $showParts;
    public bool $showMinifigs;

    public bool $isComplete;

    public bool $hasInstructions;

    public int $personalRating;

    /** @var SetPartDTO[] */
    public array $setParts = [];

    /** @var SetMinifigDTO[] */
    public array $setMinifigs = [];
}
