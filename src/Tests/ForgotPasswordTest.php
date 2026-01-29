<?php

namespace App\Tests;

use App\Entity\User\User;
use App\Entity\User\UserToken;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ForgotPasswordTest extends BaseTest
{
    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testForgotPassword()
    {
        $this->loadFixtures();
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy([]);


        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/forgot-password', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user->getEmail(),
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('message', $json);
        $this->assertEquals('Password reset code sent', $json['message']);
        $this->assertArrayHasKey('token', $json);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception|DecodingExceptionInterface
     */
    public function testResetPassword()
    {

        $options = [
            'tokenType' => UserToken::TYPE_PASSWORD_RESET,
        ];

        $this->loadFixtures($options);
        $userTokens = $this->getEntityManager()
            ->getRepository(UserToken::class)
            ->findAll();


        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/check-token-code', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'token' => $userTokens[0]->getToken(),
                'code' => $userTokens[0]->getCode(),
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($json['message'], 'Reset password successful');
        $this->assertArrayHasKey('token', $json);

    }

    /**
     * @return void
     * @throws TransportExceptionInterface
     * @throws Exception|DecodingExceptionInterface
     */
    public function testSavePassword()
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
        $token = $json['token'];

        $response = $client->request('PATCH', 'http://legolibrary-dev/api/user/patch', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'plainPassword' => 'Gravity36#',
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($json['message'], 'User updated successfully');

        // retrieve a token
        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity36#',
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $json);
    }

}
