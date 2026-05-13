<?php

namespace App\Controller\User;

use App\Repository\UserDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SavePushTokenController extends AbstractController
{
    public function __construct(
        private readonly UserDataRepository     $userDataRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security               $security,
    ) {}

    #[Route('/api/push-token', name: 'save_push_token', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $data  = json_decode($request->getContent(), true);
        $token = trim((string) ($data['pushToken'] ?? ''));

        if ($token === '') {
            return new JsonResponse(['message' => 'pushToken is required'], Response::HTTP_BAD_REQUEST);
        }

        $userData = $user->getUserData();
        if (!$userData) {
            return new JsonResponse(['message' => 'User data not found'], Response::HTTP_NOT_FOUND);
        }

        $userData->setPushToken($token);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Push token saved'], Response::HTTP_OK);
    }
}
