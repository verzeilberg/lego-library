<?php
namespace App\Controller\User;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the activation of a new user
 */
class ActivateAccount extends AbstractController
{
    /**
     * @param UserService $userService
     */
    public function __construct(private readonly UserService $userService) {}

    /**
     * Handles the invocation of the object.
     *
     * @param Request $request The HTTP request instance.
     *
     * @return JsonResponse The JSON response after processing the token code.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->userService->checkTokenCode($request->attributes->get('dto'));
    }
}
