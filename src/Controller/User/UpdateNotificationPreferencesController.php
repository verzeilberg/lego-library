<?php

namespace App\Controller\User;

use App\Repository\UserDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UpdateNotificationPreferencesController extends AbstractController
{
    public function __construct(
        private readonly Security               $security,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/api/notification-preferences', name: 'api_notification_preferences_update', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUserData();
        if (!$userData) {
            return new JsonResponse(['message' => 'User data not found'], Response::HTTP_NOT_FOUND);
        }

        $prefs = json_decode($request->getContent(), true);
        if (!is_array($prefs)) {
            return new JsonResponse(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $current = $userData->getNotificationPreferences();
        $userData->setNotificationPreferences(array_merge($current, $prefs));
        $this->em->flush();

        return new JsonResponse(['notificationPreferences' => $userData->getNotificationPreferences()]);
    }
}
