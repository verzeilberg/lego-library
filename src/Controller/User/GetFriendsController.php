<?php

namespace App\Controller\User;

use App\Repository\FriendshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetFriendsController extends AbstractController
{
    public function __construct(
        private readonly FriendshipRepository $friendshipRepository,
        private readonly Security $security,
        private readonly UploaderHelper $uploaderHelper,
    ) {}

    #[Route('/api/friends', name: 'api_friends_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();

        $formatUser = function ($ud, int $friendshipId): array {
            return [
                'friendshipId'   => $friendshipId,
                'id'             => $ud->getId(),
                'userName'       => $ud->getUserName(),
                'firstName'      => $ud->getFirstName(),
                'lastName'       => $ud->getLastName(),
                'geslacht'       => $ud->getGeslacht()?->value,
                'profilePicture' => $ud->getFilePath()
                    ? $this->uploaderHelper->asset($ud, 'file')
                    : null,
            ];
        };

        $friends = array_map(function ($friendship) use ($userData, $formatUser) {
            $friend = $friendship->getRequester()->getId() === $userData->getId()
                ? $friendship->getRecipient()
                : $friendship->getRequester();
            return $formatUser($friend, $friendship->getId());
        }, $this->friendshipRepository->findFriends($userData));

        $requests = array_map(function ($friendship) use ($formatUser) {
            return $formatUser($friendship->getRequester(), $friendship->getId());
        }, $this->friendshipRepository->findPendingReceived($userData));

        return new JsonResponse([
            'friends'         => array_values($friends),
            'pendingRequests' => array_values($requests),
        ]);
    }
}