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
     */
    public function createParts(Set $set, array $parts): Set
    {
        $totalParts = 0;

        // Caches to avoid duplicates in the same EntityManager session
        $partCache = [];
        $colorCache = [];
        $partColorCache = [];

        foreach ($parts as $row) {
            $partNumber = $row['part']['part_num'];
            $colorId   = $row['color']['id'];
            $partColorKey = $partNumber . '-' . $colorId;

            /** ------------------ PART ------------------ */
            if (isset($partCache[$partNumber])) {
                $part = $partCache[$partNumber];
            } else {
                $part = $this->partRepository->findOneBy(['partNumber' => $partNumber]);
                if (!$part) {
                    $partColor = $this->rebrickableClient->getPartByPartNumberAndColorId($partNumber, $colorId);
                    $part = new Part();
                    $part->setPartNumber($partNumber)
                        ->setName($row['part']['name']);
                    $part->setImgUrl($partColor['part_img_url']);
                    $this->entityManager->persist($part);
                }
                $partCache[$partNumber] = $part;
            }


            /** ------------------ COLOR ------------------ */
            if (isset($colorCache[$colorId])) {
                $color = $colorCache[$colorId];
            } else {
                $colorData = $row['color'];
                $color = $this->colorRepository->find($colorId);
                if (!$color) {
                    $color = new Color();
                    $color->setId($colorData['id'])
                        ->setName($colorData['name'])
                        ->setRgb($colorData['rgb'])
                        ->setIsTrans($colorData['is_trans']);
                    $this->entityManager->persist($color);
                }
                $colorCache[$colorId] = $color;
            }

            /** ------------- PART COLOR ---------------- */
            if (isset($partColorCache[$partColorKey])) {
                $partColor = $partColorCache[$partColorKey];
            } else {
                $partColor = $this->partColorRepository->findPartColorByPartAndColor($part, $color);
                if (!$partColor) {
                    $partColor = new PartColor();
                    $partColor->setPart($part)->setColor($color);
                    $this->entityManager->persist($partColor);
                }
                $partColorCache[$partColorKey] = $partColor;
            }



            /** ------------- SET PART ---------------- */
            $setPart = $this->setPartRepository->findOneBy([
                'model' => $set,
                'partColor' => $partColor
            ]);




            if (!$setPart) {
                $setPart = new SetPart();
                $setPart->setModel($set)
                    ->setPartColor($partColor)
                    ->setQuantity($row['quantity']);
                $this->entityManager->persist($setPart);

            }
        }

        $this->entityManager->flush();

        return $set;
    }

}
