<?php

namespace App\DataFixtures;

use App\Constant\JwtActions;
use App\Entity\Lego\SetList;
use App\Entity\User\User;
use App\Entity\User\UserData;
use App\Entity\User\UserToken;
use App\Service\TokenService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModelListFixtures extends Fixture
{
    private array $options = [
        'times'     => 10,
    ];

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
            $modelList = new SetList();
            $modelList->setTitle($faker->jobTitle());
            $modelList->setDescription($faker->text());
            $modelList->setUserData();

            $manager->persist($modelList);
        }

        $manager->flush();
    }

    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }
}


