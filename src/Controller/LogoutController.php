<?php
namespace App\Controller;

use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class LogoutController extends AbstractController
{
    private RefreshTokenService $refreshTokenService;
    private Security $security;

    public function __construct(
        RefreshTokenService $refreshTokenService,
        Security $security
    ) {
        $this->refreshTokenService = $refreshTokenService;
        $this->security = $security;
    }

    /**
     * Handles user logout by revoking refresh tokens based on the provided input.
     *
     * @param Request $request The HTTP request containing the logout parameters.
     *
     * @return JsonResponse Returns a JSON response indicating the success or failure of the logout operation.
     */
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? null;
        $logoutAll = $data['allDevices'] ?? false; // optional flag to log out everywhere

        // Get currently logged-in user if access token is provided
        $user = $this->security->getUser();

        if ($logoutAll) {
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], 401);
            }
            // Revoke all refresh tokens for this user
            $this->refreshTokenService->revokeAllForUser($user->getId());
        } elseif ($refreshToken) {
            // Revoke only the token sent by the client
            $this->refreshTokenService->revoke($refreshToken);
        } else {
            return new JsonResponse(['error' => 'No refresh token provided'], 400);
        }

        return new JsonResponse(['success' => true]);
    }
}

