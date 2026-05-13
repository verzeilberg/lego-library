<?php

namespace App\Controller\User;

use App\Repository\UserDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetPublicUser extends AbstractController
{
    public function __construct(
        private readonly UserDataRepository $userDataRepository,
        private readonly UploaderHelper     $uploaderHelper,
    ) {}

    #[Route('/api/public/user/{id}', name: 'api_public_user', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $userData = $this->userDataRepository->find($id);

        if (!$userData) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $profilePicture = $userData->getFilePath()
            ? $this->uploaderHelper->asset($userData, 'file')
            : null;

        return new JsonResponse([
            'id'             => $userData->getId(),
            'userName'       => $userData->getUserName(),
            'firstName'      => $userData->getFirstName(),
            'lastName'       => $userData->getLastName(),
            'bio'            => $userData->getBio(),
            'geslacht'       => $userData->getGeslacht()?->value,
            'profilePicture' => $profilePicture,
        ]);
    }
}
