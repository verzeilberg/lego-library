<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\CreateSetRequest;
use App\Entity\Lego\Color;
use App\Entity\Lego\Minifig;
use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetMinifig;
use App\Entity\Lego\SetPart;
use App\Repository\Lego\ColorRepository;
use App\Repository\Lego\MiniFigRepository;
use App\Repository\Lego\PartColorRepository;
use App\Repository\Lego\PartRepository;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetPartRepository;
use App\Repository\Lego\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MinifigService
{
    public function __construct(
        private MinifigRepository $minifigRepository,
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createMinifigs(Set $set, array $items, int $batchSize = 50): Set
    {
        // Cache existing minifigs for fast lookup
        $existingMinifigs = [];
        foreach ($set->getSetMinifigs() as $link) {
            $existingMinifigs[$link->getMinifig()->getId()] = $link;
        }

        $i = 0;
        foreach ($items as $item) {
            $minifigId = $item['id'];

            // 1. Find or create Minifig
            $minifig = $this->minifigRepository->find($minifigId);
            if (!$minifig) {
                $minifig = new Minifig();
                $minifig->setId($minifigId);
                $minifig->setSetNumId($item['set_num']);
                $minifig->setName($item['set_name']);
                $minifig->setImageUrl($item['set_img_url']);

                $this->entityManager->persist($minifig);
            }

            // 2. Check existing join row using cache
            if (isset($existingMinifigs[$minifigId])) {
                $link = $existingMinifigs[$minifigId];
            } else {
                $link = new SetMinifig();
                $link->setSet($set);
                $link->setMinifig($minifig);

                $set->addSetMinifig($link);
                $minifig->addSetLink($link);

                $this->entityManager->persist($link);
                $existingMinifigs[$minifigId] = $link;
            }

            // 3. Set quantity
            $link->setQuantity($item['quantity'] ?? 1);

            $i++;

            // 4. Batch flush
            if ($i % $batchSize === 0) {
                $this->entityManager->flush();
            }
        }

        // Flush any remaining entities
        $this->entityManager->flush();

        return $set;
    }


}
