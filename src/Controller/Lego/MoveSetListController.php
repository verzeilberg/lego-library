<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Repository\Lego\SetListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class MoveSetListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SetListRepository      $setListRepository,
    ) {}

    public function __invoke(string $id, Request $request, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->entityManager->find(SetList::class, $id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Board not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        $isOwner = $setList->getUserData() === $userData;
        $isInSharedBoard = !$isOwner && $setList->getParentList() !== null && $setList->getParentList()->isSharedWith($userData);
        if (!$isOwner && !$isInSharedBoard) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        if ($setList->getParentList() === null) {
            return new JsonResponse(['message' => 'Cannot move a top-level board'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $targetParentId = $data['targetParentId'] ?? null;
        if (!$targetParentId) {
            return new JsonResponse(['message' => 'Target parent board ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $targetParent = $this->entityManager->find(SetList::class, $targetParentId);
        if (!$targetParent) {
            return new JsonResponse(['message' => 'Target board not found'], Response::HTTP_NOT_FOUND);
        }

        $isTargetOwner = $targetParent->getUserData() === $userData;
        $isTargetShared = !$isTargetOwner && $targetParent->isSharedWith($userData);
        if (!$isTargetOwner && !$isTargetShared) {
            return new JsonResponse(['message' => 'Target board does not belong to you or is not shared with you'], Response::HTTP_FORBIDDEN);
        }

        if ($targetParent === $setList->getParentList()) {
            return new JsonResponse(['message' => 'Board is already in this parent'], Response::HTTP_BAD_REQUEST);
        }

        if ($targetParent === $setList) {
            return new JsonResponse(['message' => 'Cannot move a board into itself'], Response::HTTP_BAD_REQUEST);
        }

        $setList->setParentList($targetParent);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Board moved successfully'], Response::HTTP_OK);
    }
}
