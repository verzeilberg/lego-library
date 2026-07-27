<?php

namespace App\Controller\Lego;

use App\Service\Lego\SetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteSetFromSetListController extends AbstractController
{
    private SetService $setService;

    public function __construct(SetService $setService)
    {
        $this->setService = $setService;
    }

    public function __invoke(string $bordid, string $setnr, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->setService->deleteSetFromSetList($bordid, $setnr, $user->getUserData());
    }
}
