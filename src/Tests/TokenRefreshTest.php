<?php

namespace App\Tests;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TokenRefreshTest extends BaseTest
{
    private function loginAndGetTokens(string $password = 'Gravity35#'): array
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

        return [$client, $json['token'], $json['refresh_token'] ?? null, $user[0]];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testRefreshTokenReturnsNewJwt(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token, $refreshToken] = $this->loginAndGetTokens();

        $this->assertNotNull($refreshToken, 'Login response must include a refresh_token');

        $response = $client->request('POST', 'http://legolibrary-dev/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'refreshToken' => $refreshToken,
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('token', $json);
        $this->assertArrayHasKey('refreshToken', $json);
        $this->assertArrayHasKey('oldRefreshToken', $json);
        $this->assertNotEmpty($json['token']);
        $this->assertEquals($refreshToken, $json['oldRefreshToken']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testRefreshWithNoTokenReturns400(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', 'http://legolibrary-dev/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testRefreshWithInvalidTokenReturns401(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', 'http://legolibrary-dev/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'refreshToken' => 'invalid-or-nonexistent-refresh-token',
            ],
        ]);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testNewTokenIsUsableForAuthenticatedRequest(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token, $refreshToken] = $this->loginAndGetTokens();

        $refreshResponse = $client->request('POST', 'http://legolibrary-dev/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['refreshToken' => $refreshToken],
        ]);

        $newToken = $refreshResponse->toArray()['token'];

        $client->request('GET', 'http://legolibrary-dev/api/user-data/', [
            'auth_bearer' => $newToken,
        ]);

        $this->assertResponseIsSuccessful();
    }
}
