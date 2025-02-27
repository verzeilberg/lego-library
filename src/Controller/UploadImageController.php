<?php

namespace App\Controller;

use App\Dto\Request\User\ProfileRequest;
use App\Entity\MediaObject;
use App\Entity\PasswordResetToken;
use App\Entity\UserData;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadImageController extends AbstractController
{
    private $userRepository;
    private $entityManager;
    private $mailer;

    private $uploaderHelper;

    private readonly UserService $userService;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UserService $userService,
        UploaderHelper  $uploaderHelper
    )
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->userService = $userService;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function __invoke(
        Request $request,
        Security $security
    ): JsonResponse
    {

        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $imageFile = $request->files->get('file');

        if (!$imageFile) {
            return new JsonResponse(['message' => 'File is required!'], Response::HTTP_BAD_REQUEST);
        }

        $userData = $user->getUserData();
        $userData->setFile($imageFile);
        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $profileRequest = new ProfileRequest();
        $profileRequest->setUserName($userData->getUsername());
        $profileRequest->setFirstName($userData->getFirstName());
        $profileRequest->setLastName($userData->getLastName());
        $profileRequest->setEmail($user->getEmail());
        //Get the full file/image path
        $path = $this->uploaderHelper->asset($userData, 'file');
        $profileRequest->setProfilePicture($path);

        return new JsonResponse($profileRequest);
    }
}
