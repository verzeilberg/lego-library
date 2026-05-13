<?php

namespace App\Tests;

use App\Entity\User\User;
use App\Entity\User\UserData;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserProfileTest extends BaseTest
{
    /**
     * Returns a JWT token for the first fixture user.
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function getTokenForFirstUser(string $password = 'Gravity35#'): array
    {
        $client = self::createClient();

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => $password,
            ],
        ]);

        $json = $response->toArray();

        return [$client, $json['token'], $user[0]];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetUserDataRequiresAuthentication(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $client->request('GET', 'http://legolibrary-dev/api/user-data/');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetUserDataWithValidToken(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->getTokenForFirstUser();

        $client->request('GET', 'http://legolibrary-dev/api/user-data/', [
            'auth_bearer' => $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testPatchUserFirstName(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $token = $response->toArray()['token'];

        $response = $client->request('PATCH', 'http://legolibrary-dev/api/user/patch', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'firstName' => 'UpdatedName',
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('message', $json);
        $this->assertEquals('User updated successfully', $json['message']);

        // Verify the name was actually updated in the database
        $this->getEntityManager()->clear();
        $userData = $this->getEntityManager()
            ->getRepository(UserData::class)
            ->findAll();
        $this->assertEquals('UpdatedName', $userData[0]->getFirstName());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testPatchUserLastName(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $token = $response->toArray()['token'];

        $response = $client->request('PATCH', 'http://legolibrary-dev/api/user/patch', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'lastName' => 'UpdatedLastName',
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertEquals('User updated successfully', $json['message']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testPatchUserPasswordAndReLogin(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $loginResponse = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        // Change password
        $client->request('PATCH', 'http://legolibrary-dev/api/user/patch', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'plainPassword' => 'NewPassword99#',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Re-login with new password
        $reLoginResponse = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'NewPassword99#',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $reLoginResponse->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testPatchUserWithoutAuthenticationReturns401(): void
    {
        $client = self::createClient();

        $client->request('PATCH', 'http://legolibrary-dev/api/user/patch', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'firstName' => 'ShouldFail',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
