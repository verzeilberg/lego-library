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

class UpdateUserTest extends BaseTest
{
    private function loginAndGetToken(string $password = 'Gravity35#'): array
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

        return [$client, $response->toArray()['token'], $user[0]];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testUpdateUserRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'extra' => [
                'parameters' => [
                    'firstName' => 'NewName',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * The controller sets all fields from request; always provide firstName+lastName together
     * to avoid TypeError from a non-nullable setter receiving null.
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testUpdateUserFirstName(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $userData = $this->getEntityManager()->getRepository(UserData::class)->findAll();
        $existingLastName = $userData[0]->getLastName();

        $response = $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'extra' => [
                'parameters' => [
                    'firstName' => 'UpdatedFirstName',
                    'lastName'  => $existingLastName,
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->getEntityManager()->clear();
        $updated = $this->getEntityManager()
            ->getRepository(UserData::class)
            ->findAll();

        $this->assertEquals('UpdatedFirstName', $updated[0]->getFirstName());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testUpdateUserLastName(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $userData = $this->getEntityManager()->getRepository(UserData::class)->findAll();
        $existingFirstName = $userData[0]->getFirstName();

        $response = $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'extra' => [
                'parameters' => [
                    'firstName' => $existingFirstName,
                    'lastName'  => 'UpdatedLastName',
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->getEntityManager()->clear();
        $updated = $this->getEntityManager()
            ->getRepository(UserData::class)
            ->findAll();

        $this->assertEquals('UpdatedLastName', $updated[0]->getLastName());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testUpdateUserBio(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $userData = $this->getEntityManager()->getRepository(UserData::class)->findAll();
        $existingFirstName = $userData[0]->getFirstName();
        $existingLastName  = $userData[0]->getLastName();

        $response = $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'extra' => [
                'parameters' => [
                    'firstName' => $existingFirstName,
                    'lastName'  => $existingLastName,
                    'bio'       => 'This is my LEGO bio.',
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->getEntityManager()->clear();
        $updated = $this->getEntityManager()
            ->getRepository(UserData::class)
            ->findAll();

        $this->assertEquals('This is my LEGO bio.', $updated[0]->getBio());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testUpdateUserUsernameAndReturnContainsUserData(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'extra' => [
                'parameters' => [
                    'userName'  => 'lego_fan_42',
                    'firstName' => 'Lego',
                    'lastName'  => 'Fan',
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();

        $this->assertArrayHasKey('userName', $json);
        $this->assertEquals('lego_fan_42', $json['userName']);
    }
}
