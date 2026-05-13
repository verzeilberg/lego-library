<?php

namespace App\Controller\Lego;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Media\MediaObject;
use App\Service\ImageModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadSetImagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UploaderHelper         $uploaderHelper,
        private readonly ImageModerationService $moderationService
    ) {}

    public function __invoke(
        string $number,
        string $listId,
        Request $request
    ): JsonResponse {

        if (!$number || !$listId) {
            return new JsonResponse(
                ['error' => 'Set number and listId are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $set = $this->entityManager
            ->getRepository(Set::class)
            ->find($number);

        if (!$set) {
            return new JsonResponse(['error' => 'Set not found'], 404);
        }

        $setList = $this->entityManager
            ->getRepository(SetList::class)
            ->find($listId);

        if (!$setList) {
            return new JsonResponse(['error' => 'Set list not found'], 404);
        }

        // Find the join entity
        $link = $this->entityManager
            ->getRepository(SetListSet::class)
            ->findOneBy([
                'set' => $set,
                'setList' => $setList,
            ]);

        if (!$link) {
            return new JsonResponse(
                ['error' => 'Set is not part of this list'],
                400
            );
        }

        $uploadedFiles = $request->files->get('files');

        if (!$uploadedFiles || count($uploadedFiles) === 0) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        $mediaObjects = [];

        foreach ($uploadedFiles as $file) {
            // 🔒 Run moderation check BEFORE saving
            $isSafe = $this->moderationService->isImageSafe($file->getPathname());
            if (!$isSafe) {
                return new JsonResponse(
                    ['error' => 'One of the uploaded images contains explicit or unsafe content'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $media = new MediaObject();
            $media->setFile($file);

            // attach to join entity
            $media->setSetListSet($link);
            $link->addMediaObject($media);

            $this->entityManager->persist($media);

            $mediaObjects[] = $media;
        }

        $this->entityManager->flush();

        $response = array_map(
            fn (MediaObject $media) => [
                'id' => $media->getId(),
                'contentUrl' => $this->uploaderHelper->asset($media, 'file'),
            ],
            $mediaObjects
        );

        return new JsonResponse($response, Response::HTTP_CREATED);
    }
}

