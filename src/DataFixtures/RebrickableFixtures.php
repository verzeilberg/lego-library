<?php

namespace App\DataFixtures;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\Theme;
use App\Repository\Lego\ThemeRepository;
use App\Service\Lego\MinifigService;
use App\Service\Lego\PartService;
use App\Service\Lego\RebrickableClient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RebrickableFixtures extends Fixture implements DependentFixtureInterface
{
    private const SET_NUMBERS = [
        '6285', '6277', '6769', '6761', '6382',
        '6593', '6542', '6543', '6379', '10179', '7256',
    ];

    // Maps list reference → set base numbers assigned to that list
    private const LIST_ASSIGNMENTS = [
        FullDataFixtures::LIST_STAR_WARS => ['10179', '7256'],
        FullDataFixtures::LIST_EPISODE_IV => ['10179'],
        FullDataFixtures::LIST_WISHLIST   => ['6285', '6277', '6769', '6761', '6593'],
        FullDataFixtures::LIST_CITY       => ['6382', '6542', '6543', '6379'],
    ];

    private string $uploadDir;

    /** @var array<int, Theme> */
    private array $themeCache = [];

    /** @var array<string, Set> keyed by base number (e.g. '6285') */
    private array $createdSets = [];

    public function __construct(
        private readonly RebrickableClient $client,
        private readonly ThemeRepository   $themeRepository,
        private readonly PartService       $partService,
        private readonly MinifigService    $minifigService,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        $this->uploadDir = $projectDir . '/public/media/lego';
    }

    public function getDependencies(): array
    {
        return [FullDataFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::SET_NUMBERS as $setNumber) {
            echo "  Fetching set {$setNumber}...\n";

            try {
                $data = $this->client->getSetById($setNumber);
            } catch (\Exception $e) {
                echo "  [SKIP] {$setNumber}: " . $e->getMessage() . "\n";
                sleep(1);
                continue;
            }

            $theme = $this->resolveTheme($data['theme_id'], $manager);
            sleep(1);

            $set = new Set();
            $set->setNumber($data['set_num']);
            $set->setBaseNumber(explode('-', $data['set_num'])[0]);
            $set->setName($data['name']);
            $set->setYear((int) $data['year']);
            $set->setNumParts((int) $data['num_parts']);
            $set->setTotalParts((int) $data['num_parts']);
            $set->setTotalPartsQuantity((int) $data['num_parts']);
            $set->setTotalMiniFigParts(0);
            $set->setRating(0.0);
            $set->setTheme($theme);
            $set->setCreatedAt(new \DateTimeImmutable());

            if (!empty($data['set_img_url'])) {
                $filename = $this->downloadImage($data['set_img_url'], $setNumber);
                if ($filename) {
                    $set->setFilePath($filename);
                }
            }

            $manager->persist($set);
            $manager->flush();

            $this->createdSets[$setNumber] = $set;

            try {
                $partsResponse = $this->client->getPartsBySetId($setNumber);
                sleep(1);
                $set = $this->partService->createParts($set, $partsResponse['results'] ?? []);
            } catch (\Exception $e) {
                echo "  [WARN] Parts for {$setNumber}: " . $e->getMessage() . "\n";
            }

            try {
                $minifigsResponse = $this->client->getMiniFigsBySetNumber($setNumber);
                sleep(1);
                $set = $this->minifigService->createMinifigs($set, $minifigsResponse['results'] ?? []);
            } catch (\Exception $e) {
                echo "  [WARN] Minifigs for {$setNumber}: " . $e->getMessage() . "\n";
            }

            echo "  Done: {$data['name']}\n";
        }

        $this->createSetListSets($manager);
    }

    private function createSetListSets(ObjectManager $manager): void
    {
        foreach (self::LIST_ASSIGNMENTS as $listRef => $setNumbers) {
            $setList = $this->getReference($listRef, \App\Entity\Lego\SetList::class);

            foreach ($setNumbers as $setNumber) {
                if (!isset($this->createdSets[$setNumber])) {
                    continue;
                }

                $sls = new SetListSet();
                $sls->setSet($this->createdSets[$setNumber]);
                $sls->setSetList($setList);
                $sls->setShowImages(true);
                $sls->setShowParts(true);
                $sls->setShowMinifigs(true);
                $sls->setComplete(true);
                $sls->setInstructions(true);

                $manager->persist($sls);
            }
        }

        $manager->flush();
    }

    private function resolveTheme(int $themeId, ObjectManager $manager): Theme
    {
        if (isset($this->themeCache[$themeId])) {
            return $this->themeCache[$themeId];
        }

        $existing = $this->themeRepository->findOneBy(['themeId' => $themeId]);
        if ($existing) {
            $this->themeCache[$themeId] = $existing;
            return $existing;
        }

        try {
            $data  = $this->client->getThemeById($themeId);
            sleep(1);
            $theme = (new Theme())
                ->setThemeId($data['id'])
                ->setName($data['name'])
                ->setParentThemeId($data['parent_id'] ?? null);
        } catch (\Exception) {
            $theme = (new Theme())->setThemeId($themeId)->setName('Unknown');
        }

        $manager->persist($theme);
        $manager->flush();

        $this->themeCache[$themeId] = $theme;

        return $theme;
    }

    private function downloadImage(string $url, string $setNumber): ?string
    {
        $filename = 'set-' . $setNumber . '.jpg';
        $path     = $this->uploadDir . '/' . $filename;

        if (!file_exists($path)) {
            $content = @file_get_contents($url);
            if ($content === false) {
                return null;
            }
            file_put_contents($path, $content);
        }

        return $filename;
    }
}
