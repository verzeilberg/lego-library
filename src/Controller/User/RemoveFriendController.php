<?php

namespace App\Controller\User;

use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RemoveFriendController extends AbstractController
{
    public function __construct(
        private readonly FriendshipRepository $friendshipRepository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {}

    #[Route('/api/friends/{friendshipId}', name: 'api_friends_remove', methods: ['DELETE'])]
    public function __invoke(int $friendshipId): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();
        $friendship = $this->friendshipRepository->find($friendshipId);

        if (!$friendship) {
            return new JsonResponse(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $isParty = $friendship->getRequester()->getId() === $userData->getId()
            || $friendship->getRecipient()->getId() === $userData->getId();

        if (!$isParty) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($friendship);
        $this->em->flush();

        return new JsonResponse(['message' => 'Removed']);
    }
}
