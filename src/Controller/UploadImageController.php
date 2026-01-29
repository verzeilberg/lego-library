<?php

namespace App\Controller;

use App\Entity\Lego\Set;
use App\Entity\Media\MediaObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadImageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UploaderHelper $uploaderHelper;

    public function __construct(EntityManagerInterface $entityManager, UploaderHelper $uploaderHelper)
    {
        $this->entityManager = $entityManager;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Expect the Set number as a query parameter or form-data field
        $setNumber = $request->get('setNumber');
        if (!$setNumber) {
            return new JsonResponse(['error' => 'Set number is required'], Response::HTTP_BAD_REQUEST);
        }

        $set = $this->entityManager->getRepository(Set::class)->find($setNumber);
        if (!$set) {
            return new JsonResponse(['error' => 'Set not found'], Response::HTTP_NOT_FOUND);
        }

        // Accept multiple files if needed
        $uploadedFiles = $request->files->all();
        if (empty($uploadedFiles)) {
            return new JsonResponse(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $mediaObjects = [];

        foreach ($uploadedFiles as $file) {
            if (!$file) {
                continue;
            }

            $mediaObject = new MediaObject();
            $mediaObject->setFile($file);

            // Option 1: assign Set via ManyToOne relation
            if (method_exists($mediaObject, 'setSet')) {
                $mediaObject->setSet($set);
                $set->addMediaObject($mediaObject);
            }

            // Option 2: if you don’t want a ManyToOne, use a simple setNumber column
            // $mediaObject->setSetNumber($setNumber);

            $this->entityManager->persist($mediaObject);
            $mediaObjects[] = $mediaObject;
        }

        $this->entityManager->flush();

        // Return uploaded objects with contentUrl
        $response = array_map(function (MediaObject $mediaObject) {
            return [
                'id' => $mediaObject->getId(),
                'contentUrl' => $this->uploaderHelper->asset($mediaObject, 'file'),
            ];
        }, $mediaObjects);

        return new JsonResponse($response, Response::HTTP_CREATED);
    }
}
