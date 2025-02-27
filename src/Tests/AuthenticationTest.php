<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AuthenticationTest extends BaseTest
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testLogin(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        // retrieve a token
        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $json);

        $userDataId = $user[0]->getUserData()->getId();

        //Testing if we're getting a 401 because we don't send the token
        $client->request('GET', 'http://legolibrary-dev/api/user-data/'. $userDataId);
        $this->assertResponseStatusCodeSame(401);

        //Testing if we're getting a 200 because we send the token with the request
        $client->request('GET', 'http://legolibrary-dev/api/user-data/'. $userDataId, ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();
    }
}
