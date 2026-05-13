<?php

namespace App\Tests;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DeleteUserTest extends BaseTest
{
    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteUserRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('DELETE', 'http://legolibrary-dev/api/user-data/delete');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testDeleteUserRemovesUserFromDatabase(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $users = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $this->assertCount(1, $users);

        $loginResponse = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $users[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        $deleteResponse = $client->request('DELETE', 'http://legolibrary-dev/api/user-data/delete', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $json = $deleteResponse->toArray();
        $this->assertEquals(Response::HTTP_OK, $deleteResponse->getStatusCode());
        $this->assertArrayHasKey('result', $json);
        $this->assertEquals('User deleted successfully', $json['result']);

        $this->getEntityManager()->clear();
        $remainingUsers = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $this->assertCount(0, $remainingUsers);
    }
}
