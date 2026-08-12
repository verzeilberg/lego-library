<?php

namespace App\Tests;

use App\Entity\Lego\SetList;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class EditBoardTest extends BaseTest
{
    private function loginAndGetToken(string $email, string $password): array
    {
        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => ['email' => $email, 'password' => $password],
        ]);
        return [$client, $response->toArray()['token']];
    }

    // -------------------------------------------------------------------------
    // PUT /api/set-list  (same endpoint as create, but with an id field)
    // -------------------------------------------------------------------------

    public function testEditBoardRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'extra' => [
                'parameters' => [
                    'id'          => 'some-id',
                    'title'       => 'Updated Title',
                    'publicPrivate' => 'true',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testEditBoardTitleAndDescription(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $setList   = $this->getEntityManager()->getRepository(SetList::class)->findAll();
        $setListId = (string) $setList[0]->getId();

        $response = $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'extra'   => [
                'parameters' => [
                    'id'            => $setListId,
                    'title'         => 'Updated Board Title',
                    'description'   => 'Updated board description',
                    'publicPrivate' => 'true',
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertArrayHasKey('title', $json);
        $this->assertEquals('Updated Board Title', $json['title']);
        $this->assertArrayHasKey('description', $json);
        $this->assertEquals('Updated board description', $json['description']);

        // Verify persisted to DB
        $this->getEntityManager()->clear();
        $updated = $this->getEntityManager()->find(SetList::class, $setListId);
        $this->assertEquals('Updated Board Title', $updated->getTitle());
        $this->assertEquals('Updated board description', $updated->getDescription());
    }

    public function testEditBoardWithImage(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $setList   = $this->getEntityManager()->getRepository(SetList::class)->findAll();
        $setListId = (string) $setList[0]->getId();

        $uploadedFilePath = sys_get_temp_dir() . '/test_board_image.jpg';
        copy(__DIR__ . '/../../fixtures/image.jpg', $uploadedFilePath);
        $file = new UploadedFile($uploadedFilePath, 'image.jpg', null, null, false);

        $response = $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'extra'   => [
                'parameters' => [
                    'id'            => $setListId,
                    'title'         => 'Board With New Image',
                    'description'   => 'Updated with a fresh cover image',
                    'publicPrivate' => 'true',
                ],
                'files' => ['file' => $file],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        fwrite(STDERR, "\nResponse: " . json_encode($json) . "\n");
        $this->assertArrayHasKey('title', $json);
        $this->assertEquals('Board With New Image', $json['title']);
        $this->assertArrayHasKey('contentUrl', $json);
        $this->assertNotNull($json['contentUrl']);
    }

    public function testEditBoardReturns404ForNonExistentBoard(): void
    {
        $options = [
            'times'    => 1,
            'password' => 'Gravity35#',
            'active'   => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $response = $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'extra'   => [
                'parameters' => [
                    'id'            => '00000000-0000-0000-0000-000000000000',
                    'title'         => 'Should Not Exist',
                    'publicPrivate' => 'true',
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
