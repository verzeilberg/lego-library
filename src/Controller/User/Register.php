<?php
namespace App\Controller\User;

use App\Entity\UserData;
use App\Service\UserService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Handles the registration of a new user
 */
#[AsController]
class Register extends AbstractController
{
    /**
     * @param UserService $userService
     */
    public function __construct(private readonly UserService $userService) {}

    /**
     * Handles the invocation of the user registration process.
     *
     * @param Request $request The HTTP request instance containing attributes.
     * @return JsonResponse The response after user registration.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->userService->register($request->attributes->get('dto'));
    }

}
