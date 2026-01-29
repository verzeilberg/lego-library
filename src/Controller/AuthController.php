<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    private $jwtManager;
    private $passwordEncoder;
    private $userRepository;
    private $refreshTokenService;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordEncoder,
        UserRepository $userRepository,
        RefreshTokenService $refreshTokenService
    ) {
        $this->jwtManager = $jwtManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->refreshTokenService = $refreshTokenService;
    }

    /**
     * Authenticates a user based on the provided email and password.
     *
     * Validates user credentials and ensures the account is active.
     * Generates a JWT token and a refresh token for the authenticated session.
     *
     * @param Request $request The HTTP request containing the login credentials.
     *
     * @return JsonResponse A JSON response with a JWT token and refresh token,
     *                      or an error message if validation fails.
     */
    public function login(Request $request): JsonResponse
    {
        $postedData = json_decode($request->getContent(), true);
        $email = $postedData['email'];
        $password = $postedData['password'];

        // Check if a user exists
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Check if an account is active
        if (!$user->isActive()) {
            return new JsonResponse(['error' => 'Account is not activated'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Generate a refresh token and store it
        $refreshToken = $this->refreshTokenService->create($user->getId());

        return new JsonResponse([
            'token' => $token,
            'refresh_token' => $refreshToken,
        ]);
    }
}
