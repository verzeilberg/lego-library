<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class RefreshController extends AbstractController
{
    private RefreshTokenService $refreshService;
    private JWTTokenManagerInterface $jwtManager;
    private $userRepository;

    public function __construct(
        RefreshTokenService $refreshService,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository
    ) {
        $this->refreshService = $refreshService;
        $this->jwtManager = $jwtManager;
        $this->userRepository = $userRepository;
    }

    /**
     * Handles the token refresh operation when a valid refresh token is provided.
     *
     * @param Request $request The HTTP request containing the refresh token payload.
     *
     * @return JsonResponse Returns a JSON response with a new JWT token, a newly issued refresh token,
     *                      and the old refresh token. In case of errors, returns an error message with
     *                      an appropriate HTTP status code.
     */
    #[Route('/api/token/refresh', name: 'token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? null;

        if (!$refreshToken) {
            return new JsonResponse(['error' => 'No refresh token provided'], 400);
        }

        $userId = $this->refreshService->validate($refreshToken);
        if (!$userId) {
            return new JsonResponse(['error' => 'Invalid or expired refresh token'], 401);
        }

        // Generate JWT token
        $user = $this->userRepository->find($userId);
        $token = $this->jwtManager->create($user);

        // Optionally issue a new refresh token
        $newRefreshToken = $this->refreshService->create($userId);


        $this->refreshService->revoke($refreshToken);

        return new JsonResponse([
            'token' => $token,
            'refreshToken' => $newRefreshToken,
            'oldRefreshToken' => $refreshToken,
        ]);
    }
}
