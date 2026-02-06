<?php

namespace App\Dto\Request\Lego;

use Symfony\Component\Serializer\Annotation\Groups;

final class RateSetRequest
{
    #[Groups(['setList:read'])]
    public string $setId;

    #[Groups(['setList:read'])]
    public float $rating;

    public function __construct(string $setId, float $rating)
    {
        $this->setId = $setId;
        $this->rating = $rating;
    }
}
