<?php

namespace App\Controller\User;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\UserData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PatchController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface  $userPasswordHasher
    )
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function __invoke(
        Request            $request,
        ValidatorInterface $validator
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not authenticated.');
        }

        $dto = $request->attributes->get('dto');

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, 400);
        }

        /** @var UserData $userData */
        $userData = $user->getUserData();

        if (isset($dto->firstName)) {
            $userData->setFirstName($dto->firstName);
        }

        if (isset($dto->lastName)) {
            $userData->setLastName($dto->lastName);
        }

        if (isset($dto->userName)) {
            $userData->setUserName($dto->userName);
        }

        if (isset($dto->plainPassword)) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $dto->plainPassword));
        }

        $this->entityManager->persist($user);
        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User updated successfully'], 200);
    }
}
