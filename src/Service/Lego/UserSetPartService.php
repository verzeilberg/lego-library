<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\CreateDefectPartsRequest;
use App\Entity\Lego\UserSetPart;
use App\Repository\Lego\ColorRepository;
use App\Repository\Lego\PartColorRepository;
use App\Repository\Lego\PartRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Repository\Lego\SetPartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class UserSetPartService
{
    public function __construct(
        private SetListSetRepository   $setListSetRepository,
        private SetPartRepository      $setPartRepository,
        private PartRepository         $partRepository,
        private ColorRepository       $colorRepository,
        private PartColorRepository    $partColorRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function createOrUpdateDefectPart(CreateDefectPartsRequest $dto): JsonResponse
    {
        $setListSet = $this->setListSetRepository->findBySetNumberAndListId(
            $dto->setNumber,
            $dto->bordId
        );

        if ($setListSet === null) {
            return new JsonResponse(
                ['error' => 'Set not found on the given list.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $part = $this->partRepository->find($dto->partId);
        if ($part === null) {
            return new JsonResponse(
                ['error' => 'Part not found for this set'],
                Response::HTTP_NOT_FOUND
            );
        }

        $color = $this->colorRepository->find($dto->colorId);
        if ($color === null) {
            return new JsonResponse(
                ['error' => 'Color not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $partColor = $this->partColorRepository->findPartColorByPartAndColor($part, $color);
        if ($partColor === null) {
            return new JsonResponse(
                ['error' => 'Combination part and color not found for this set'],
                Response::HTTP_NOT_FOUND
            );
        }

        $setPart = $this->setPartRepository->findOneByModelAndPartColor($setListSet->getSet(), $partColor);



        if ($setPart === null) {
            return new JsonResponse(
                ['error' => sprintf('Part (%s) with the given color (%s) not found in this set (%s).', $dto->partId, $partColor->getId(),$dto->setNumber )],
                Response::HTTP_NOT_FOUND
            );
        }

        $userSetPart = $this->entityManager->getRepository(UserSetPart::class)->findOneBy([
            'setListSet' => $setListSet,
            'setPart'    => $setPart,
        ]);

        $allZero = $dto->missingQuantity === 0
            && $dto->damagedQuantity === 0
            && $dto->discolouredQuantity === 0;

        if ($allZero) {
            if ($userSetPart !== null) {
                $this->entityManager->remove($userSetPart);
            }
        } else {
            if ($userSetPart === null) {
                $userSetPart = new UserSetPart();
                $userSetPart->setSetListSet($setListSet);
                $userSetPart->setSetPart($setPart);
                $this->entityManager->persist($userSetPart);
            }

            $userSetPart->setMissingQuantity($dto->missingQuantity);
            $userSetPart->setDamagedQuantity($dto->damagedQuantity);
            $userSetPart->setDiscolouredQuantity($dto->discolouredQuantity);
        }

        $remainingDefects = $setListSet->getPartStates()->filter(
            fn(UserSetPart $p) => $p !== $userSetPart && $p->getTotalDefective() > 0
        );
        $hasDefects = !$allZero || $remainingDefects->count() > 0;
        $setListSet->setComplete(!$hasDefects);

        $this->entityManager->flush();

        if ($allZero) {
            return new JsonResponse([
                'missingQuantity'     => 0,
                'damagedQuantity'     => 0,
                'discolouredQuantity' => 0,
                'totalDefective'      => 0,
                'isComplete'          => true,
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'missingQuantity'     => $userSetPart->getMissingQuantity(),
            'damagedQuantity'     => $userSetPart->getDamagedQuantity(),
            'discolouredQuantity' => $userSetPart->getDiscolouredQuantity(),
            'totalDefective'      => $userSetPart->getTotalDefective(),
            'isComplete'          => $userSetPart->isComplete(),
        ], Response::HTTP_OK);
    }
}
