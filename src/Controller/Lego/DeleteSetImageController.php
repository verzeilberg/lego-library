<?php

namespace App\Controller\Lego;

use App\Entity\Media\MediaObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteSetImageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $mediaId = $request->attributes->get('id');
        $media = $this->entityManager->getRepository(MediaObject::class)->find($mediaId);

        if ($media === null) {
            return new JsonResponse(['error' => 'Image not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
