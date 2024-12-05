<?php

namespace App\Controller;

use App\Dto\Request\User\ForgotPasswordRequest;
use App\Entity\MediaObject;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Entity\UserData;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadImageController extends AbstractController
{
    private $userRepository;
    private $entityManager;
    private $mailer;

    private readonly UserService $userService;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UserService $userService
    )
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->userService = $userService;
    }

    public function __invoke($id,Request $request): JsonResponse
    {
        $userData = $this->entityManager->getRepository(UserData::class)->findOneByOwner($id);

        if (!$userData) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $imageFile = $request->files->get('file');

        if (!$imageFile) {
            return new JsonResponse(['message' => 'File is required!'], Response::HTTP_BAD_REQUEST);
        }

        $mediaObject = new MediaObject();
        $mediaObject->file = $imageFile;

        $userData->setImage($mediaObject);

        $this->entityManager->persist($mediaObject);
        $this->entityManager->persist($userData);
        $this->entityManager->flush();


        return new JsonResponse($mediaObject);
    }
}
