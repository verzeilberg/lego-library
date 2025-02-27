<?php

namespace App\Controller\User;

use App\Dto\Request\User\ForgotPasswordRequest;
use App\Entity\PasswordResetToken;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckTokenCodeController extends AbstractController
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

    public function __invoke(Request $request): JsonResponse
    {
        return $this->userService->checkTokenCode($request->attributes->get('dto'));
    }
}
