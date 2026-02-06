<?php
namespace App\Dto\Request\Lego;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class CreateSetRequest
{

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['model:read', 'model:create', 'model:update'])]
    public string $id;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['model:read', 'model:create', 'model:update'])]
    public string $legoNmbr;

    #[Assert\NotNull]
    #[Groups(['model:read', 'model:create', 'model:update'])]
    public bool $addLegoImages = false;

    #[Assert\NotNull]
    #[Groups(['model:read', 'model:create', 'model:update'])]
    public bool $addLegoParts = false;

    #[Assert\NotNull]
    #[Groups(['model:read', 'model:create', 'model:update'])]
    public bool $addLegoMinifigs = false;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return CreateSetRequest
     */
    public function setId(string $id): CreateSetRequest
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegoNmbr(): string
    {
        return $this->legoNmbr;
    }

    /**
     * @param string $legoNmbr
     * @return CreateSetRequest
     */
    public function setLegoNmbr(string $legoNmbr): CreateSetRequest
    {
        $this->legoNmbr = $legoNmbr;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAddLegoImages(): bool
    {
        return $this->addLegoImages;
    }

    /**
     * @param bool $addLegoImages
     * @return CreateSetRequest
     */
    public function setAddLegoImages(bool $addLegoImages): CreateSetRequest
    {
        $this->addLegoImages = $addLegoImages;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAddLegoParts(): bool
    {
        return $this->addLegoParts;
    }

    /**
     * @param bool $addLegoParts
     * @return CreateSetRequest
     */
    public function setAddLegoParts(bool $addLegoParts): CreateSetRequest
    {
        $this->addLegoParts = $addLegoParts;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAddLegoMinifigs(): bool
    {
        return $this->addLegoMinifigs;
    }

    /**
     * @param bool $addLegoMinifigs
     * @return CreateSetRequest
     */
    public function setAddLegoMinifigs(bool $addLegoMinifigs): CreateSetRequest
    {
        $this->addLegoMinifigs = $addLegoMinifigs;
        return $this;
    }



}
