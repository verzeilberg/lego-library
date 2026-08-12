<?php

namespace App\Tests;

use App\Entity\Lego\SetList;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Tests authentication and basic input validation for Lego set endpoints.
 * External API calls (Rebrickable) are not tested here; only auth and
 * request validation is verified.
 */
class LegoSetAuthTest extends BaseTest
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

    // -------------------------------------------------------------------------
    // POST /api/lego/sets/create
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testCreateSetRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['setNumber' => '75192'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // GET /api/lego/set-lists/{listId}/sets/{number}
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetSetByListAndNumberRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', 'http://legolibrary-dev/api/lego/set-lists/some-list-id/sets/75192');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /api/lego/set-lists/{listId}/sets/{number}/add-images
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testUploadSetImagesRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/lego/set-lists/some-list-id/sets/75192/add-images');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/lego/list/{bordid}/set/{setnr}
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteSetFromSetListRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('DELETE', 'http://legolibrary-dev/api/lego/list/some-list-id/set/75192');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /api/lego/sets/rate-set
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testRateSetRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/lego/sets/rate-set', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['setId' => 1, 'rating' => 5],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /api/lego/set/part/defect/create
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testCreateDefectPartsRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/lego/set/part/defect/create', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/set-images/delete/{id}
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteSetImageRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('DELETE', 'http://legolibrary-dev/api/set-images/delete/00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // Authenticated requests with missing/invalid data
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCreateSetWithMissingSetNumberReturnsError(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [],
        ]);

        // Expects 422 (validation) or 400 (bad request) — not 200 or 401
        $this->assertNotEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetSetByNonExistentListReturns404(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request(
            'GET',
            'http://legolibrary-dev/api/lego/set-lists/00000000-0000-0000-0000-000000000000/sets/75192',
            ['auth_bearer' => $token]
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
