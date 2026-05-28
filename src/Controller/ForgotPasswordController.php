<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ForgotPasswordController extends AbstractController
{
    private readonly UserService $userService;

    public function __construct(
        UserService $userService
    )
    {
        $this->userService = $userService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->userService->forgotPassword($request->attributes->get('dto'));
    }
}
