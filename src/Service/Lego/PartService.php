<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\CreateSetRequest;
use App\Entity\Lego\Color;
use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetPart;
use App\Repository\Lego\ColorRepository;
use App\Repository\Lego\PartColorRepository;
use App\Repository\Lego\PartRepository;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetPartRepository;
use App\Repository\Lego\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        foreach ($parts as $row) {
            $partNumbers[$row['part']['part_num']] = true;
            $colorIds[$row['color']['id']] = true;
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
                    ->setName($row['part']['name'])
                    ->setImgUrl($row['part']['part_img_url'] ?? null);
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

        // Flush any remaining entities
        $this->entityManager->flush();

        return $set;
    }


}
