<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\CreateDefectPartsRequest;
use App\Service\Lego\UserSetPartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class CreateDefectPartsController extends AbstractController
{
    public function __construct(
        private readonly UserSetPartService $userSetPartService,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateDefectPartsRequest::class,
            'json'
        );

        return $this->userSetPartService->createOrUpdateDefectPart($dto);
    }
}
