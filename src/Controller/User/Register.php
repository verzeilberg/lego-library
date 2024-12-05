<?php
namespace App\Controller\User;

use App\Service\UserService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @param Request $request
     *
     * @return User
     */
    public function __invoke(Request $request): User
    {
        return $this->userService->register($request->attributes->get('dto'));
    }
}
