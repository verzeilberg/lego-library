<?php

namespace App\Service;

use App\Constant\JwtActions;
use App\Dto\Request\User\TokenCodeRequest;
use App\Dto\Request\User\ForgotPasswordRequest;
use App\Dto\Request\User\ImageUploadRequest;
use App\Dto\Request\User\ProfileRequest;
use App\Dto\Request\User\RegisterUserRequest;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Entity\UserData;
use App\Entity\UserToken;
use App\Exception\NotFoundException;
use App\Repository\UserTokenRepository;
use App\Repository\UserDataRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UserService
{
    public function __construct(
        private EntityManagerInterface                $entityManager,
        private readonly EmailService                 $emailService,
        private readonly TokenService                 $tokenService,
        private readonly JWTTokenManagerInterface     $tokenManager,
        private readonly UserRepository               $userRepository,
        private readonly UserDataRepository           $userDataRepository,
        private readonly UserTokenRepository          $userTokenRepository,
        private readonly UserPasswordHasherInterface  $userPasswordHasher,
        private readonly ValidatorInterface           $validator,
        private readonly UploaderHelper               $uploaderHelper,
    )
    {}

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
        $userData = $this->userDataRepository->find($id);

            $profile = new ProfileRequest();
            $profile->setUserName($userData->getUserName());
            $profile->setFirstName($userData->getFirstName());
            $profile->setLastName($userData->getLastName());
            $profile->setEmail($userData->getOwner()->getEmail());
            //Get the full file/image path
            $path = $this->uploaderHelper->asset($userData, 'file');
            $profile->setProfilePicture($path);

            return $profile;
    }

    /**
     * @param ImageUploadRequest $request
     * @return void
     */
    public function uploadImage(ImageUploadRequest $request) {
        $image = new MediaObject();
        $image->setFilename($request->getName());
        $image->setPath($request->getUri());
        $this->entityManager->persist($image);
        $this->entityManager->flush();

    }

    /**
     * @param RegisterUserRequest $request
     * @return JsonResponse
     */
    public function register(RegisterUserRequest $request): JsonResponse
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

        $userProfile = new ProfileRequest();

        $userProfile->setUserName($userData->getUserName());
        $userProfile->setFirstName($userData->getFirstName());
        $userProfile->setLastName($userData->getLastName());
        $userProfile->setEmail($userData->getOwner()->getEmail());

        $token = $this->tokenManager->createFromPayload($user, ['sub' => $user->getId(), 'action' => JwtActions::ACTIVATE_ACCOUNT]);
        $code = $this->tokenService->generate4DigitCode($token);

        // Create password reset token
        $userToken = new UserToken($user, $token, $code, UserToken::TYPE_USER_ACTIVATION);
        $this->entityManager->persist($userToken);
        $this->entityManager->flush();

        $this->emailService->sendToUser('account/welcome', $userProfile, 'Confirm your account', [
            'tokenCode' => $userToken->getCode(),
            'user' => $userProfile,
        ]);

        return new JsonResponse(
            [
                'message' => 'Activation account code sent',
                'token' => $userToken->getToken()
            ]
        );

    }

    /**
     * @param string $token
     * @return User
     *
     * @throws NotFoundException
     */
    public function activate(TokenCodeRequest $request): JsonResponse
    {
        $decodedToken = $this->tokenManager->parse($request->token);
        $user = $this->userRepository->find($decodedToken['sub']);
        if (null === $user) {
            throw NotFoundException::createEntityNotFoundException('User');
        }

        //Set user on active
        $user->setActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->tokenService->generateToken($user);

        return new JsonResponse(
            [
                'token' => $token,
                'message' => 'Account is activated',
            ]
        );
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

        $token = $this->tokenManager->createFromPayload($user, ['sub' => $user->getId(), 'action' => JwtActions::FORGOT_PASSWORD]);
        $code = $this->tokenService->generate4DigitCode($token);

        // Create password reset token
        $passwordResetToken = new UserToken($user, $token, $code, UserToken::TYPE_PASSWORD_RESET);
        $this->entityManager->persist($passwordResetToken);
        $this->entityManager->flush();

        $userProfile = new ProfileRequest();
        $userData = $user->getUserData();
        $userProfile->setUserName($userData->getUserName());
        $userProfile->setFirstName($userData->getFirstName());
        $userProfile->setLastName($userData->getLastName());
        $userProfile->setEmail($user->getEmail());

        $this->emailService->sendToUser('account/reset-password', $userProfile, 'Forget your password', [
            'tokenCode' => $passwordResetToken->getCode(),
            'user' => $userProfile,
        ]);

        return new JsonResponse(
            [
                'message' => 'Password reset code sent',
                'token' => $passwordResetToken->getToken()
            ]
        );
    }

    /**
     * @param UserToken $userToken
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function resetPassword(UserToken $userToken): JsonResponse
    {
        $decodedToken = $this->tokenManager->parse($userToken->getToken());
        $user = $this->userRepository->find($decodedToken['sub']);
        if (null === $user) {
            throw NotFoundException::createEntityNotFoundException('User');
        }

        $token = $this->tokenService->generateToken($user);

        return new JsonResponse(
            [
                'token' => $token,
                'message' => 'Reset password successful',
            ]
        );
    }

    /**
     * Checks the validity of a given token with a corresponding code.
     *
     * @param string $token The token to be validated.
     * @param string $code The code to be checked against the token.
     *
     * @return JsonResponse|User Returns true if the token code is valid, otherwise false.
     * @throws NotFoundException
     */
    public function checkTokenCode(TokenCodeRequest $request): JsonResponse|User
    {
        $token = $request->getToken();
        $code = $request->getCode();
        $userToken = $this->userTokenRepository->findOneBy(['token' => $token, 'code' => $code]);
        if (!$userToken) {
            return new JsonResponse(['message' => 'Code not found'], 404);
        }

        if ($userToken->isExpired()) {
            //Token is expired so we don't need it anymore
            $this->userTokenRepository->deleteByToken($token);
            return new JsonResponse(['message' => 'Code expired'], 498);
        }

        $userId = $userToken->getUser()->getId();
        //Delete token because we don't need it anymore
        $this->userTokenRepository->deleteByToken($token);

        $userTokenType = $userToken->getType();

        switch ($userTokenType) {
            case UserToken::TYPE_USER_ACTIVATION:
                return $this->activate($request);
            case UserToken::TYPE_PASSWORD_RESET:
                return $this->resetPassword($userToken);
        }

        return new JsonResponse(
            [
                'message' => 'Something went wrong.',
                'userId' => $userId,
                'token' => $token,
                'success' => false,
            ]
        );

    }

}
