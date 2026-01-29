<?php

namespace App\Repository\Lego;

use App\Entity\Lego\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    /**
     * @param int $themeId
     * @param string $name
     * @param int|null $parentThemeId
     * @return Theme
     */
    public function getOrCreateByThemeId(
        int    $themeId,
        string $name,
        ?int   $parentThemeId = null
    ): Theme
    {
        $entityManager = $this->getEntityManager();

        $theme = $this->findOneBy(['themeId' => $themeId]);

        if ($theme instanceof Theme) {
            return $theme;
        }

        $theme = new Theme();
        $theme
            ->setThemeId($themeId)
            ->setName($name)
            ->setParentThemeId($parentThemeId);

        $entityManager->persist($theme);
        $entityManager->flush();

        return $theme;
    }

}
