<?php

namespace App\Controller\Lego;


use App\Service\Lego\SetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DeleteSetFromSetListController extends AbstractController
{
    private SetService $setService;

    public function __construct(SetService $setService)
    {
        $this->setService = $setService;
    }
    public function __invoke(string $bordid, string $setnr): JsonResponse
    {
        return $this->setService->deleteSetFromSetList($bordid, $setnr);

    }
}
