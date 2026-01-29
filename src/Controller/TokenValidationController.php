<?php
// src/Controller/TokenValidationController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class TokenValidationController extends AbstractController
{
    public function __construct(
        private Security            $security,
        private JWTEncoderInterface $jwtEncoder // service for decoding raw token
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Missing or malformed Authorization header',
            ], 400);
        }

        $token = substr($authHeader, 7);

        try {
            // Decode token claims (exp, roles, etc.)
            $decoded = $this->jwtEncoder->decode($token);

            if (!$decoded) {
                return new JsonResponse([
                    'valid' => false,
                    'message' => 'Invalid token',
                ], 401);
            }

            // Also check if Symfony recognized the user
            $user = $this->security->getUser();

            return new JsonResponse([
                'valid' => true,
                'username' => $user?->getUserIdentifier(),
                'claims' => $decoded,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Invalid or expired token',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
