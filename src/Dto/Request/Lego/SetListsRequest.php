<?php

namespace App\Dto\Request\Lego;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

final class SetListsRequest
{
    #[Groups(['setList:read'])]
    public string $id;

    #[Groups(['setList:read'])]
    public string $title;

    #[Groups(['setList:read'])]
    public ?string $description = null;

    #[Groups(['setList:read'])]
    public bool $isPublic = true;

    #[Groups(['setList:read'])]
    public bool $isSet = false;

    #[ApiProperty(types: ['https://schema.org/MediaObject'])]
    #[Groups(['setList:read'])]
    public ?string $filePath = null;

    #[Groups(['setList:read'])]
    public ?array $owner = null;

    #[Groups(['setList:read'])]
    public ?string $parentId = null;

    #[Groups(['setList:read'])]
    public bool $shared = false;

    #[Groups(['setList:read'])]
    public ?array $sharedWith = null;

    public function __construct(
        string $id,
        string $title,
        ?string $description,
        bool $isPublic,
        bool $isSet,
        ?string $filePath,
        ?array $owner = null,
        ?string $parentId = null,
        bool $shared = false,
        ?array $sharedWith = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->isPublic = $isPublic;
        $this->isSet = $isSet;
        $this->filePath = $filePath;
        $this->owner = $owner;
        $this->parentId = $parentId;
        $this->shared = $shared;
        $this->sharedWith = $sharedWith;
    }

}
