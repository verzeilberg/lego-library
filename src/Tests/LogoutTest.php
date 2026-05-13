<?php

namespace App\Tests;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LogoutTest extends BaseTest
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
    public function testLogoutWithRefreshToken(): void
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

        $response = $client->request('POST', 'http://legolibrary-dev/api/logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'refreshToken' => $refreshToken,
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('success', $json);
        $this->assertTrue($json['success']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testLogoutWithoutTokenReturns400(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetTokens();

        $response = $client->request('POST', 'http://legolibrary-dev/api/logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [],
        ]);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testLogoutAllDevicesWithValidAuth(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetTokens();

        $response = $client->request('POST', 'http://legolibrary-dev/api/logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'allDevices' => true,
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('success', $json);
        $this->assertTrue($json['success']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testLogoutAllDevicesWithoutAuthReturns401(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', 'http://legolibrary-dev/api/logout', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'allDevices' => true,
            ],
        ]);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
