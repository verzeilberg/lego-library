<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Repository\Lego\SetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class MoveSetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SetListRepository      $setListRepository,
        private readonly SetListSetRepository   $setListSetRepository,
        private readonly SetRepository          $setRepository,
    ) {}

    public function __invoke(string $listId, string $setNumber, Request $request, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $sourceList = $this->entityManager->find(SetList::class, $listId);
        if (!$sourceList) {
            return new JsonResponse(['message' => 'Source board not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        $isSourceOwner = $sourceList->getUserData() === $userData;
        $isSourceShared = !$isSourceOwner && $sourceList->isSharedWith($userData);
        if (!$isSourceOwner && !$isSourceShared) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $targetListId = $data['targetListId'] ?? null;
        if (!$targetListId) {
            return new JsonResponse(['message' => 'Target board ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $targetList = $this->entityManager->find(SetList::class, $targetListId);
        if (!$targetList) {
            return new JsonResponse(['message' => 'Target board not found'], Response::HTTP_NOT_FOUND);
        }

        $isTargetOwner = $targetList->getUserData() === $userData;
        $isTargetShared = !$isTargetOwner && $targetList->isSharedWith($userData);
        if (!$isTargetOwner && !$isTargetShared) {
            return new JsonResponse(['message' => 'Target board does not belong to you or is not shared with you'], Response::HTTP_FORBIDDEN);
        }

        if ($targetList === $sourceList) {
            return new JsonResponse(['message' => 'Source and target boards are the same'], Response::HTTP_BAD_REQUEST);
        }

        $set = $this->setRepository->find($setNumber);
        if (!$set) {
            return new JsonResponse(['message' => 'Set not found'], Response::HTTP_NOT_FOUND);
        }

        $setListSet = $this->setListSetRepository->findOneBy([
            'set' => $set,
            'setList' => $sourceList,
        ]);

        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set not found in source board'], Response::HTTP_NOT_FOUND);
        }

        $existingLink = $this->setListSetRepository->findOneBy([
            'set' => $set,
            'setList' => $targetList,
        ]);

        if ($existingLink) {
            return new JsonResponse(['message' => 'Set already exists in target board'], Response::HTTP_CONFLICT);
        }

        $setListSet->setSetList($targetList);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Set moved successfully'], Response::HTTP_OK);
    }
}
