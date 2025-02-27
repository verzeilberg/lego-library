<?php

namespace App\Controller;

use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ResetPasswordController extends AbstractController
{
    private $tokenRepository;
    private $entityManager;
    private $passwordHasher;

    public function __construct(UserTokenRepository $tokenRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->tokenRepository = $tokenRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/reset-password', methods: ['POST'])]
    public function __invoke(Request $request, ValidatorInterface $validator): JsonResponse
    {
        die(' sdasdsd');

        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $code = $data['code'] ?? null;
        $newPassword = $data['newPassword'] ?? null;
        $newPasswordVerify = $data['newPassword'] ?? null;

        if (!$token || !$code || !$newPassword || !$newPasswordVerify) {
            return new JsonResponse(['message' => 'Invalid data'], 400);
        }

        // Find reset token
        $passwordResetToken = $this->tokenRepository->findOneBy(['token' => $token, 'code' => $code]);

        if (!$passwordResetToken || $passwordResetToken->isExpired()) {
            return new JsonResponse(['message' => 'Invalid or expired token'], 400);
        }

        if ($newPassword !== $newPasswordVerify) {
            return new JsonResponse(['message' => 'Passwords do not match'], 400);
        }

        // Update user's password
        $user = $passwordResetToken->getUser();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        // Optionally, remove the reset token after use
        $this->entityManager->remove($passwordResetToken);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Password has been reset']);
    }
}
