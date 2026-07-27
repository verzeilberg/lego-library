<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Repository\UserDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UnshareSetListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserDataRepository $userDataRepository,
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

        if ($setList->getUserData() !== $user->getUserData()) {
            return new JsonResponse(['message' => 'Only the board owner can unshare'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userDataRepository->find($userId);
        if (!$targetUser) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$setList->isSharedWith($targetUser)) {
            return new JsonResponse(['message' => 'Board is not shared with this user'], Response::HTTP_NOT_FOUND);
        }

        $setList->removeSharedWith($targetUser);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Board unshared successfully'], Response::HTTP_OK);
    }
}
