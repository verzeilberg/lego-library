<?php

namespace App\Controller\User;

use App\Entity\User\Friendship;
use App\Repository\FriendshipRepository;
use App\Repository\UserDataRepository;
use App\Service\EmailService;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SendFriendRequestController extends AbstractController
{
    public function __construct(
        private readonly FriendshipRepository    $friendshipRepository,
        private readonly UserDataRepository      $userDataRepository,
        private readonly EntityManagerInterface  $em,
        private readonly Security                $security,
        private readonly PushNotificationService $pushNotificationService,
        private readonly EmailService            $emailService,
    ) {}

    #[Route('/api/friends/request/{userId}', name: 'api_friends_request', methods: ['POST'])]
    public function __invoke(int $userId): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();
        $recipient = $this->userDataRepository->find($userId);

        if (!$recipient) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($userData->getId() === $recipient->getId()) {
            return new JsonResponse(['message' => 'Cannot send a request to yourself'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->friendshipRepository->findBetween($userData, $recipient)) {
            return new JsonResponse(['message' => 'A request or friendship already exists'], Response::HTTP_CONFLICT);
        }

        $friendship = (new Friendship())
            ->setRequester($userData)
            ->setRecipient($recipient);

        $this->em->persist($friendship);
        $this->em->flush();

        $senderName = $userData->getUserName()
            ?? trim($userData->getFirstName() . ' ' . $userData->getLastName());
        $recipientName = trim($recipient->getFirstName() . ' ' . $recipient->getLastName());

        if ($recipient->getNotificationPref('friendRequestPush') && ($token = $recipient->getPushToken())) {
            $this->pushNotificationService->send([$token], 'Nieuw vriendschapsverzoek', $senderName . ' wil bevriend met je zijn');
        }

        if ($recipient->getNotificationPref('friendRequestEmail') && ($email = $recipient->getOwner()?->getEmail())) {
            $this->emailService->send('social/friend-request', $email, 'Nieuw vriendschapsverzoek', [
                'senderName'    => $senderName,
                'recipientName' => $recipientName,
            ]);
        }

        return new JsonResponse(['message' => 'Friend request sent', 'requestId' => $friendship->getId()]);
    }
}