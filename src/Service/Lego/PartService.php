<?php

namespace App\Service\Lego;

use App\Entity\Lego\Color;
use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetPart;
use App\Repository\Lego\ColorRepository;
use App\Repository\Lego\PartColorRepository;
use App\Repository\Lego\PartRepository;
use App\Repository\Lego\SetPartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class PartService
{
    public function __construct(
        private PartRepository         $partRepository,
        private ColorRepository        $colorRepository,
        private PartColorRepository    $partColorRepository,
        private SetPartRepository      $setPartRepository,
        private EntityManagerInterface $entityManager,
        private RebrickableClient      $rebrickableClient,
        private HttpClientInterface    $httpClient,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    )
    {}

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ORMException
     */
    public function createParts(Set $set, array $parts, int $batchSize = 50): Set
    {
        // -------------------- 1. Prepare caches --------------------
        $partNumbers = [];
        $colorIds = [];
        $totalPartsQuantity = 0;
        $totalParts = 0;
        foreach ($parts as $row) {
            $partNumbers[$row['part']['part_num']] = true;
            $colorIds[$row['color']['id']] = true;
            if( !$row['is_spare']) {
                $totalPartsQuantity = (int) $totalPartsQuantity + (int) $row['quantity'];
                $totalParts++;
            }
        }

        $partNumbers = array_keys($partNumbers);
        $colorIds = array_keys($colorIds);

        // -------------------- 2. Preload existing Parts --------------------
        $existingParts = $this->partRepository->findBy(['partNumber' => $partNumbers]);
        $partCache = [];
        foreach ($existingParts as $p) {
            $partCache[$p->getPartNumber()] = $p;
        }

        // -------------------- 3. Preload existing Colors --------------------
        $existingColors = $this->colorRepository->findBy(['id' => $colorIds]);
        $colorCache = [];
        foreach ($existingColors as $c) {
            $colorCache[$c->getId()] = $c;
        }

        // -------------------- 4. Preload existing PartColors --------------------
        $partColorCache = [];
        if (!empty($partCache) && !empty($colorCache)) {
            $qb = $this->partColorRepository->createQueryBuilder('pc')
                ->where('pc.part IN (:parts)')
                ->andWhere('pc.color IN (:colors)')
                ->setParameter('parts', $partCache)
                ->setParameter('colors', $colorCache);

            $existingPartColors = $qb->getQuery()->getResult();
            foreach ($existingPartColors as $pc) {
                $key = $pc->getPart()->getPartNumber() . '-' . $pc->getColor()->getId();
                $partColorCache[$key] = $pc;
            }
        }

        // -------------------- 5. Process parts --------------------
        $i = 0;
        foreach ($parts as $row) {
            $partNumber = $row['part']['part_num'];
            $colorId = $row['color']['id'];
            $quantity = $row['quantity'];
            $partColorKey = $partNumber . '-' . $colorId;

            // --- PART ---
            if (isset($partCache[$partNumber])) {
                $part = $partCache[$partNumber];
            } else {
                $part = new Part();
                $part->setPartNumber($partNumber)
                    ->setName($row['part']['name']);
                $this->entityManager->persist($part);
                $partCache[$partNumber] = $part;
            }

            // --- COLOR ---
            if (isset($colorCache[$colorId])) {
                $color = $colorCache[$colorId];
            } else {
                $colorData = $row['color'];
                $color = new Color();
                $color->setId($colorData['id'])
                    ->setName($colorData['name'])
                    ->setRgb($colorData['rgb'])
                    ->setIsTrans($colorData['is_trans']);
                $this->entityManager->persist($color);
                $colorCache[$colorId] = $color;
            }

            // --- PARTCOLOR ---
            if (isset($partColorCache[$partColorKey])) {
                $partColor = $partColorCache[$partColorKey];
            } else {
                $partColor = new PartColor();
                $partColor->setPart($part)->setColor($color);
                $remoteImgUrl = $row['part_img_url'] ?? $row['part']['part_img_url'] ?? null;
                if ($remoteImgUrl) {
                    $local = $this->downloadPartColorImage($remoteImgUrl, $partNumber, $colorId);
                    $partColor->setImgUrl($local ?? $remoteImgUrl);
                }
                $this->entityManager->persist($partColor);
                $partColorCache[$partColorKey] = $partColor;
            }

            // --- SETPART ---
            $setPart = $this->setPartRepository->findOneBy([
                'model' => $set,
                'partColor' => $partColor
            ]);

            if (!$setPart) {
                $setPart = new SetPart();
                $setPart->setModel($set)
                    ->setPartColor($partColor)
                    ->setQuantity($quantity);
                $this->entityManager->persist($setPart);
            }

            $i++;

            // -------------------- 6. Batch flush --------------------
            if ($i % $batchSize === 0) {
                $this->entityManager->flush();
            }
        }

        $set->setTotalPartsQuantity($totalPartsQuantity);
        $set->setTotalParts($totalParts);

        // Flush any remaining entities
        $this->entityManager->flush();

        return $set;
    }

    private function downloadPartColorImage(string $url, string $partNum, int $colorId): ?string
    {
        $partsDir = $this->projectDir . '/public/media/lego/parts';
        if (!is_dir($partsDir)) {
            mkdir($partsDir, 0755, true);
        }

        $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'part-' . $partNum . '-' . $colorId . '.' . $ext;
        $filePath = $partsDir . '/' . $filename;

        if (file_exists($filePath)) {
            return 'parts/' . $filename;
        }

        try {
            $response = $this->httpClient->request('GET', $url);
            $content  = $response->getContent(false);
            if (empty($content)) {
                return null;
            }
            file_put_contents($filePath, $content);
            return 'parts/' . $filename;
        } catch (\Throwable) {
            return null;
        }
    }
}
