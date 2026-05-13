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

class SetListCRUDTest extends BaseTest
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
    // GET /api/set-lists-for-user
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetSetListsByUserRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', 'http://legolibrary-dev/api/set-lists-for-user');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetSetListsByUserReturnsOwnListsOnly(): void
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

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists-for-user', [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertIsArray($json);
    }

    // -------------------------------------------------------------------------
    // GET /api/set-list/get/{id}
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetSetByIdRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', 'http://legolibrary-dev/api/set-list/get/some-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetSetByIdReturnsSetListForOwner(): void
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

        $setList = $this->getEntityManager()
            ->getRepository(SetList::class)
            ->findAll();

        $this->assertNotEmpty($setList, 'Fixtures should have created at least one set list');

        $setListId = (string) $setList[0]->getId();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-list/get/' . $setListId, [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertArrayHasKey('title', $json);
        $this->assertEquals($setList[0]->getTitle(), $json['title']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/set-list/delete/{id}
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteSetListRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('DELETE', 'http://legolibrary-dev/api/set-list/delete/some-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testDeleteSetListSuccessfully(): void
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

        $setLists = $this->getEntityManager()
            ->getRepository(SetList::class)
            ->findAll();

        $this->assertNotEmpty($setLists, 'Fixtures should have created at least one set list');

        $setListId = (string) $setLists[0]->getId();

        $response = $client->request('DELETE', 'http://legolibrary-dev/api/set-list/delete/' . $setListId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('result', $json);
        $this->assertEquals('Set list successfully deleted', $json['result']);
    }

    // -------------------------------------------------------------------------
    // GET /api/set-lists/{id}  (children and sets)
    // -------------------------------------------------------------------------

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetSetListChildrenRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', 'http://legolibrary-dev/api/set-lists/some-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws TransportExportInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetSetListChildrenReturnsDataForExistingList(): void
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

        $setLists = $this->getEntityManager()
            ->getRepository(SetList::class)
            ->findAll();

        $this->assertNotEmpty($setLists);

        $setListId = (string) $setLists[0]->getId();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists/' . $setListId, [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetSetListChildrenReturns404ForNonExistentList(): void
    {
        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        [$client, $token] = $this->loginAndGetToken();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists/00000000-0000-0000-0000-000000000000', [
            'auth_bearer' => $token,
        ]);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
