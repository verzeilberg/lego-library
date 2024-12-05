<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest extends ApiTestCase
{

    public function testLogin(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $user = new User();
        $user->setEmail('test2@example.com');
        $user->setUserName('test');
        $user->setFirstName('tester');
        $user->setLastName('testing');

        $user->setPassword(
            $container->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
        );

       // $user->setPassword('$2y$13$56yXxg2we6XHB.SHfFc6p.B0Au3tcuo9vEkLudnJmrrhGQ4YB8kpm');

        $manager = $container->get('doctrine')->getManager();
        $manager->persist($user);
        $manager->flush();

        // retrieve a token
        $response = $client->request('POST', 'https://127.0.0.1/auth', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test2@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $json);

        // test not authorized
        $client->request('GET', 'https://127.0.0.1:8080/api/books');
        $this->assertResponseStatusCodeSame(401);

        // test authorized
        $client->request('GET', 'https://127.0.0.1:8080/api/books', ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();
    }
}
