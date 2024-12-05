<?php
namespace App\Controller\User;

use App\Dto\Request\User\ProfileRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Handles the registration of a new user
 */
#[AsController]
class Profile extends AbstractController
{
    /**
     * @param UserService $userService
     */
    public function __construct(private readonly UserService $userService) {}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {

        $id = $request->get('id');
        $profile = $this->userService->getProfile($id);

        return new JsonResponse($profile);
    }
}
