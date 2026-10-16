<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Repository\UserDataRepository;
use App\Service\Lego\BoardAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ShareSetListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserDataRepository $userDataRepository,
        private readonly BoardAccessService $boardAccessService,
    ) {}

    public function __invoke(string $id, string $userId, Security $security): JsonResponse
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
        if ($setList->getUserData() !== $userData) {
            return new JsonResponse(['message' => 'Only the board owner can share'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userDataRepository->find($userId);
        if (!$targetUser) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->boardAccessService->areFriends($userData, $targetUser)) {
            return new JsonResponse(['message' => 'You can only share a board with friends'], Response::HTTP_FORBIDDEN);
        }

        if ($setList->isSharedWith($targetUser)) {
            return new JsonResponse(['message' => 'Board is already shared with this user'], Response::HTTP_CONFLICT);
        }

        $setList->addSharedWith($targetUser);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Board shared successfully'], Response::HTTP_OK);
    }
}
