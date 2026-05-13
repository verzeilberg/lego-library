<?php

namespace App\Controller\User;

use App\Repository\FriendshipRepository;
use App\Service\EmailService;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AcceptFriendRequestController extends AbstractController
{
    public function __construct(
        private readonly FriendshipRepository    $friendshipRepository,
        private readonly EntityManagerInterface  $em,
        private readonly Security                $security,
        private readonly PushNotificationService $pushNotificationService,
        private readonly EmailService            $emailService,
    ) {}

    #[Route('/api/friends/accept/{requestId}', name: 'api_friends_accept', methods: ['POST'])]
    public function __invoke(int $requestId): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();
        $friendship = $this->friendshipRepository->find($requestId);

        if (!$friendship) {
            return new JsonResponse(['message' => 'Request not found'], Response::HTTP_NOT_FOUND);
        }

        if ($friendship->getRecipient()->getId() !== $userData->getId()) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($friendship->getStatus() !== 'pending') {
            return new JsonResponse(['message' => 'Request already handled'], Response::HTTP_BAD_REQUEST);
        }

        $friendship->setStatus('accepted');
        $this->em->flush();

        $accepterName = $userData->getUserName()
            ?? trim($userData->getFirstName() . ' ' . $userData->getLastName());
        $requester = $friendship->getRequester();
        $requesterName = trim($requester->getFirstName() . ' ' . $requester->getLastName());

        if ($requester->getNotificationPref('friendAcceptedPush') && ($token = $requester->getPushToken())) {
            $this->pushNotificationService->send([$token], 'Vriendschapsverzoek geaccepteerd', $accepterName . ' heeft je vriendschapsverzoek geaccepteerd');
        }

        if ($requester->getNotificationPref('friendAcceptedEmail') && ($email = $requester->getOwner()?->getEmail())) {
            $this->emailService->send('social/friend-request-accepted', $email, 'Vriendschapsverzoek geaccepteerd', [
                'accepterName'  => $accepterName,
                'requesterName' => $requesterName,
            ]);
        }

        return new JsonResponse(['message' => 'Friend request accepted']);
    }
}
