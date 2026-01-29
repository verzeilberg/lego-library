<?php

namespace App\Controller\User;

use App\Entity\User\UserData;
use App\Service\FileManager;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsController]
class UpdateUser extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly UploaderHelper         $uploaderHelper,
        private readonly FileManager            $fileManager,
    ) {}

    /**
     * @param Request $request
     * @param Security $security
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        Security $security,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        $this->updateUserDataFromRequest($userData, $request);

        $imageFile = $request->files->get('file');

        if ($imageFile) {
            $userData->setFile($imageFile);
        } else {
            $existingPath = $this->uploaderHelper->asset($userData, 'file');
            if ($existingPath !== null) {
                try {
                    $this->fileManager->deleteFile($userData, 'file', $_SERVER['DOCUMENT_ROOT'] . $existingPath);
                } catch (\RuntimeException $e) {
                    return $this->json([
                        'message' => 'Failed to delete existing profile picture',
                        'error'   => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $profile = $this->userService->getProfile($userData->getId());
        $jsonData = $serializer->serialize($profile, 'json', [
            'groups' => ['userData:read'],
        ]);

        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    /**
     * @param UserData $userData
     * @param Request $request
     * @return void
     */
    private function updateUserDataFromRequest(UserData $userData, Request $request): void
    {
        $userData->setUserName($request->get('userName'));
        $userData->setFirstName($request->get('firstName'));
        $userData->setLastName($request->get('lastName'));
        $userData->setBio($request->get('bio'));
    }
}
