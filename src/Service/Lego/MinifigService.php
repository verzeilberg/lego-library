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
    public function createMinifigs(Set $set, array $items): Set
    {
        foreach ($items as $item) {

            // 1. Find or create Minifig
            $minifig = $this->minifigRepository->find($item['id']);

            if (!$minifig) {
                $minifig = new Minifig();
                $minifig->setId($item['id']);
                $minifig->setSetNumId($item['set_num']);
                $minifig->setName($item['set_name']);
                $minifig->setImageUrl($item['set_img_url']);

                $this->entityManager->persist($minifig);
            }

            // 2. Check existing join row
            $existingLink = null;

            foreach ($set->getSetMinifigs() as $link) {
                if ($link->getMinifig()->getId() === $minifig->getId()) {
                    $existingLink = $link;
                    break;
                }
            }

            // 3. Create join if missing
            if (!$existingLink) {
                $link = new SetMinifig();
                $link->setSet($set);
                $link->setMinifig($minifig);

                $set->addSetMinifig($link);
                $minifig->addSetLink($link);

                $this->entityManager->persist($link);

                $existingLink = $link;
            }

            // 4. Set quantity
            $existingLink->setQuantity($item['quantity'] ?? 1);
        }

        return $set;
    }

}
