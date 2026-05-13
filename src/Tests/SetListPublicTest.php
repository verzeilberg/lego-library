<?php

namespace App\Tests;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SetListPublicTest extends BaseTest
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
    public function testGetPublicSetListsRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', 'http://legolibrary-dev/api/set-lists-public');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetPublicSetListsReturnsArray(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists-public', [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetPublicSetListsWithPagination(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists-public?limit=5&page=1', [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertIsArray($json);
        $this->assertLessThanOrEqual(5, count($json));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetPublicSetListsPageBeyondResultsReturnsEmptyArray(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        // A very high page number should return no results
        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists-public?limit=10&page=9999', [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(0, $response->toArray());
    }
}
