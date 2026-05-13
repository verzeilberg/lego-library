<?php

namespace App\Controller\User;

use App\Repository\FriendshipRepository;
use App\Repository\UserDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetFriendshipStatusController extends AbstractController
{
    public function __construct(
        private readonly FriendshipRepository $friendshipRepository,
        private readonly UserDataRepository $userDataRepository,
        private readonly Security $security,
    ) {}

    #[Route('/api/friends/status/{userId}', name: 'api_friends_status', methods: ['GET'])]
    public function __invoke(int $userId): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();
        $other = $this->userDataRepository->find($userId);

        if (!$other) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $friendship = $this->friendshipRepository->findBetween($userData, $other);

        if (!$friendship) {
            return new JsonResponse(['status' => 'none', 'friendshipId' => null]);
        }

        if ($friendship->getStatus() === 'accepted') {
            return new JsonResponse(['status' => 'accepted', 'friendshipId' => $friendship->getId()]);
        }

        $isSender = $friendship->getRequester()->getId() === $userData->getId();

        return new JsonResponse([
            'status'       => $isSender ? 'pending_sent' : 'pending_received',
            'friendshipId' => $friendship->getId(),
        ]);
    }
}
