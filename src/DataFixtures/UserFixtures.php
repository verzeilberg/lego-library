<?php

namespace App\DataFixtures;

use App\Constant\JwtActions;
use App\Entity\User;
use App\Entity\UserData;
use App\Entity\UserToken;
use App\Service\TokenService;
use App\State\UserPasswordHasher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private array $options = [
        'times'     => 10,
        'active'    => false,
        'userData'  => true,
        'userToken' => true,
        'tokenType' => UserToken::TYPE_USER_ACTIVATION,
        'expiresAt' => null,
    ];

    private $jwtTokenManager;
    private $tokenService;

    // Voeg de JWTTokenManagerInterface toe via de constructor
    public function __construct(
        JWTTokenManagerInterface $jwtTokenManager,
        TokenService             $tokenService,
        private readonly UserPasswordHasherInterface  $userPasswordHasher
    )
    {
        $this->jwtTokenManager  = $jwtTokenManager;
        $this->tokenService    = $tokenService;
    }

    /**
     * Loads and initializes data into the database for testing or other purposes.
     *
     * This method creates a specified number of user entities, sets their email and password,
     * and optionally flags them as active. For each user, it may also create associated
     * user data entities or user token entities depending on the provided options.
     *
     * - If the 'active' option is enabled, users are flagged as active.
     * - If the 'userData' option is enabled, user data entities are created with random information.
     * - If the 'userToken' option is enabled, user token entities are created for authentication and are associated with users.
     *
     * Data persisting is performed in batches to the database.
     *
     * @param ObjectManager $manager The object manager used to persist data.
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 1; $i <= $this->options['times']??10; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $this->options['password']??$faker->password()));
            if ($this->options['active']??false) {
                $user->setActive(true);
            }

            $manager->persist($user);
            $manager->flush();

            if ($this->options['userData'] === true) {
                $userData = new UserData();
                $userData->setFirstName($faker->firstName());
                $userData->setLastName($faker->lastName());
                $userData->setUserName($faker->userName());
                $userData->setOwner($user);

                $manager->persist($userData);
            }

            if ($this->options['userToken'] === true) {
                $token = $this->jwtTokenManager->createFromPayload($user, ['sub' => $user->getId(), 'action' => JwtActions::ACTIVATE_ACCOUNT]);
                $code = $this->tokenService->generate4DigitCode($token);
                $userToken = new UserToken($user, $token, $code, $this->options['tokenType'], $this->options['expiresAt']);
                $manager->persist($userToken);
            }

        }

        $manager->flush();
    }

    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }
}


