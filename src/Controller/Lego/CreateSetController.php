<?php
namespace App\Controller\Lego;

use App\Dto\Request\Lego\CreateSetRequest;
use App\Service\Lego\SetService;
use App\Service\Lego\RebrickableClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class CreateSetController extends AbstractController
{
    private SetService $setService;

    public function __construct(SetService $setService)
    {
        $this->setService = $setService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->setService->createSetByNumber($request->attributes->get('dto'));
    }
}
