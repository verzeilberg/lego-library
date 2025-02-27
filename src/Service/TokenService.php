<?php

namespace App\Service;

use App\Constant\JwtActions;
use App\Dto\Request\User\TokenCodeRequest;
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
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\VarDumper\VarDumper;

class TokenService
{
    private $jwtManager;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
    )
    {
        $this->jwtManager = $jwtManager;
    }

    /**
     * @param UserInterface $user
     * @return string
     */
    public function generateToken(UserInterface $user): string
    {
        return $this->jwtManager->create($user);
    }


    public function generate4DigitCode($jwtToken): string
    {
        // Split the JWT token into its parts
        $parts = explode('.', $jwtToken);
        if (count($parts) !== 3) {
            throw new Exception("Invalid JWT token");
        }

        // Decode the payload (the second part of the JWT)
        $payload = base64_decode($parts[1]);
        if ($payload === false) {
            throw new Exception("Unable to decode JWT payload");
        }

        // Hash the payload using SHA256
        $hashedValue = hash('sha256', $payload);

        // Convert the hashed value to an integer and get the last 4 digits
        $hashedInt = intval(hexdec(substr($hashedValue, 0, 8))); // Use part of the hash
        $fourDigitCode = $hashedInt % 10000;

        // Ensure it's always 4 digits, even if leading zeros are required
        return str_pad($fourDigitCode, 4, '0');
    }

}
