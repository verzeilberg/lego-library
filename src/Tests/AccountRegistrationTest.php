<?php

namespace App\Tests;

use App\Entity\User\User;
use App\Entity\User\UserToken;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AccountRegistrationTest extends BaseTest
{
    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRegistration()
    {
        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'firstName' => 'Tester',
                'lastName' => 'President',
                'email' => 'test2@test.nl',
                'plainPassword' => 'Test1234#'
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('message', $json);
        $this->assertEquals($json['message'], 'Activation account code sent');
        $this->assertArrayHasKey('token', $json);
    }

    /**
     * @return void
     * @throws TransportExceptionInterface
     */
    public function testRegistrationWithExistingEmail(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'active' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'firstName' => $user[0]->getUserData()->getFirstName(),
                'lastName' => $user[0]->getUserData()->getLastName(),
                'email' => $user[0]->getEmail(),
                'plainPassword' => 'Test123#'
            ],
        ]);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

    }

    /**
     * @return void
     * @throws TransportExceptionInterface
     */
    public function testRegistrationWithPasswordTooShort(): void
    {
        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'firstName' => 'Tester',
                'lastName' => 'President',
                'email' => 'test2@test.nl',
                'plainPassword' => 'Test#'
            ],
        ]);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    /**
     * @return void
     * @throws TransportExceptionInterface
     */
    public function testRegistrationWithInvalidEmail(): void
    {
        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'firstName' => 'Tester',
                'lastName' => 'President',
                'email' => 'test2@test',
                'plainPassword' => 'Test#testestset'
            ],
        ]);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    /**
     * Tests the 'activate account' functionality by sending a POST request
     * to the specified endpoint with test data and asserting
     * that the response status code is 404.
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testActivateAccount(): void
    {
        $this->loadFixtures();
        $userTokens = $this->getEntityManager()
            ->getRepository(UserToken::class)
            ->findAll();

        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/activate', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'token' => $userTokens[0]->getToken(),
                'code' => $userTokens[0]->getCode(),
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Account is activated', $json['message']);
    }

    /**
     * Tests the 'activate account' functionality with an expired token by
     * sending a POST request to the specified endpoint. Verifies that the
     * response status code is 498, indicating failure due to token expiration.
     * Loads fixtures with an expired token parameter for testing purposes.
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testActivateAccountWithExpiredToken(): void
    {
        $options = [
            'expiresAt' => new \DateTime('-1 hour')
        ];

        $this->loadFixtures($options);
        $userTokens = $this->getEntityManager()
            ->getRepository(UserToken::class)
            ->findAll();

        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/activate', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'token' => $userTokens[0]->getToken(),
                'code' => $userTokens[0]->getCode(),
            ],
        ]);

        $this->assertEquals(498, $response->getStatusCode());
    }

    /**
     *
     * Tests the account activation process when no token is found in the database.
     *
     * Ensures that the appropriate HTTP status code (404) is returned when
     * attempting to activate an account with a token that does not exist in the
     * database.
     *
     * - Loads fixtures with `userToken` option set to false.
     * - Sends a POST request to the API endpoint to activate the user account.
     * - Validates that the response returns the expected HTTP status code.
     *
     * @throws TransportExceptionInterface
     */
    public function testActivateAccountWithoutTokenInDatabase(): void
    {
        $options = [
            'userToken' => false
        ];

        $this->loadFixtures($options);

        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/public/user/activate', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'token' => 'test',
                'code' => 5555,
            ],
        ]);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
