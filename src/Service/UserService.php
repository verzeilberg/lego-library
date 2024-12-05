<?php

namespace App\Service;

use App\Constant\JwtActions;
use App\Dto\Request\User\ActivateAccountRequest;
use App\Dto\Request\User\ForgotPasswordRequest;
use App\Dto\Request\User\ImageUploadRequest;
use App\Dto\Request\User\ProfileRequest;
use App\Dto\Request\User\RegisterUserRequest;
use App\Entity\MediaObject;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Entity\UserData;
use App\Exception\NotFoundException;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserDataRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\VarDumper\VarDumper;

class UserService
{
    public function __construct(
        private EntityManagerInterface                $entityManager,
        private readonly EmailService                 $emailService,
        private readonly JWTTokenManagerInterface     $tokenManager,
        private readonly UserRepository               $userRepository,
        private readonly UserDataRepository           $userDataRepository,
        private readonly PasswordResetTokenRepository $passwordResetTokenRepository,
        private readonly UserPasswordHasherInterface  $userPasswordHasher,
        private readonly string                       $frontendUrl,
        private readonly ValidatorInterface           $validator,
        private readonly SerializerInterface          $serializer
    )
    {


    }

    public function exists(string $email): bool
    {
        return null !== $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * @param $id
     * @return ProfileRequest
     */
    public function getProfile($id): ProfileRequest
    {
        $userData = $this->userDataRepository->find(1);

            $profile = new ProfileRequest();
            $profile->setUserName($userData->getUserName());
            $profile->setFirstName($userData->getFirstName());
            $profile->setLastName($userData->getLastName());
            $profile->setEmail($userData->getOwner()->getEmail());
            $profile->setProfilePicture($userData->getImage()->getFile());

            return $profile;
    }

    public function uploadImage(ImageUploadRequest $request) {
        $image = new MediaObject();
        $image->setFilename($request->getName());
        $image->setPath($request->getUri());
        $this->entityManager->persist($image);
        $this->entityManager->flush();

    }

    public function register(RegisterUserRequest $request): UserData
    {
        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $request->plainPassword));
        $user->setRoles([User::ROLE_USER]);
        $user->setActive(false);

        $userData = new UserData();
        $userData->setFirstName($request->firstName);
        $userData->setLastName($request->lastName);
        $userData->setUserName($request->userName);
        $userData->setOwner($user);
        $this->entityManager->persist($user);
        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $token = $this->tokenManager->createFromPayload($user, ['sub' => $user->getId(), 'action' => JwtActions::ACTIVATE_ACCOUNT]);
        $this->emailService->sendToUser('account/welcome', $userData, 'Confirm your account', [
            'activationLink' => sprintf('%sactivate-account?token=%s', $this->frontendUrl, $token),
            'user' => $userData,
        ]);

        return $userData;
    }

    /**
     * @param string $token
     * @return User
     *
     * @throws NotFoundException
     */
    public function activate(ActivateAccountRequest $request): User
    {
        $decodedToken = $this->tokenManager->parse($request->token);
        $user = $this->userRepository->find($decodedToken['sub']);
        if (null === $user) {
            throw NotFoundException::createEntityNotFoundException('User');
        }

        $user->setActive(true);

        return $user;
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $errors = $this->validator->validate($request);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => (string)$errors], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $request->email]);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        // Create password reset token
        $passwordResetToken = new PasswordResetToken($user);
        $this->entityManager->persist($passwordResetToken);
        $this->entityManager->flush();

        $this->emailService->sendToUser('account/reset-password', $user, 'Confirm your account', [
            'tokenCode' => $passwordResetToken->getCode(),
            'user' => $user,
        ]);

        return new JsonResponse(
            [
                'message' => 'Password reset link sent',
                'token' => $passwordResetToken->getToken()
            ]
        );
    }

    /**
     * Checks the validity of a given token with a corresponding code.
     *
     * @param string $token The token to be validated.
     * @param string $code The code to be checked against the token.
     *
     * @return bool Returns true if the token code is valid, otherwise false.
     */
    public function checkTokenCode($token, $code)
    {
        $passwordResetToken = $this->passwordResetTokenRepository->findOneBy(['token' => $token, 'code' => $code]);
        if (!$passwordResetToken) {
            return new JsonResponse(['message' => 'Code not found'], 404);
        }

        if ($passwordResetToken->isExpired()) {
            //Token is expired so we don't need it anymore
            $this->passwordResetTokenRepository->deleteByToken($token);
            return new JsonResponse(['message' => 'Code expired'], 400);
        }

        $userId = $passwordResetToken->getUser()->getId();
        //Delete token because we don't need it anymore
        $this->passwordResetTokenRepository->deleteByToken($token);

        $token = $this->tokenManager->create($passwordResetToken->getUser());

        return new JsonResponse(
            [
                'message' => 'Code is valid',
                'userId' => $userId,
                'token' => $token,
                'success' => true,
            ]
        );

    }

}
