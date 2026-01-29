<?php
namespace App\Controller\User;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Handles the registration of a new user
 */
#[AsController]
class ReadUser extends AbstractController
{
    /**
     * @param UserService $userService
     */
    public function __construct(private readonly UserService $userService) {}

    /**
     * Handles the request to fetch user profile data.
     *
     * @param Request $request The HTTP request object.
     * @param Security $security The security service to retrieve the authenticated user.
     *
     * @return JsonResponse The JSON response containing the user profile
     *                      or an error message with the respective HTTP status code.
     */
    public function __invoke(
        Request $request,
        Security $security
    ): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        if (!$userData) {
            return new JsonResponse(['message' => 'User data not found'], Response::HTTP_NOT_FOUND);
        }

        $profile = $this->userService->getProfile($userData->getId());
        return new JsonResponse($profile);
    }
}
