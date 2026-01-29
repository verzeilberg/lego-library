<?php

namespace App\Entity\Lego;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Lego\ThemeRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ORM\Table(
    name: "lego_theme",
    indexes: [
        new ORM\Index(name: "idx_parent_theme", columns: ["parent_theme_id"])
    ]
)]
#[ApiResource]
class Theme
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    private ?UuidInterface $id = null;

    #[ORM\Column(type: 'integer', unique: true)]
    private int $themeId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $parentThemeId = null;

    #[ORM\Column(length: 40)]
    #[Groups(['lego_set:read'])]
    private string $name;

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getThemeId(): int
    {
        return $this->themeId;
    }

    /**
     * @param int $themeId
     * @return Theme
     */
    public function setThemeId(int $themeId): Theme
    {
        $this->themeId = $themeId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getParentThemeId(): ?int
    {
        return $this->parentThemeId;
    }

    /**
     * @param int|null $parentThemeId
     * @return Theme
     */
    public function setParentThemeId(?int $parentThemeId): Theme
    {
        $this->parentThemeId = $parentThemeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Theme
     */
    public function setName(string $name): Theme
    {
        $this->name = $name;
        return $this;
    }



}
