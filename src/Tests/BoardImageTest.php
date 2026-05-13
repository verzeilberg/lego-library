<?php

namespace App\Tests;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\Theme;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests uploading user images to a set within a board
 * (POST /api/lego/set-lists/{listId}/sets/{number}/add-images).
 */
class BoardImageTest extends BaseTest
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

    private function createSetAndLinkToList(SetList $setList): Set
    {
        $em = $this->getEntityManager();

        $theme = (new Theme())->setThemeId(1)->setName('Pirates');
        $em->persist($theme);

        $set = new Set();
        $set->setNumber('6285-1');
        $set->setBaseNumber('6285');
        $set->setName('Black Seas Barracuda');
        $set->setYear(1989);
        $set->setNumParts(900);
        $set->setTotalParts(900);
        $set->setTotalPartsQuantity(900);
        $set->setTotalMiniFigParts(0);
        $set->setRating(0.0);
        $set->setTheme($theme);
        $set->setCreatedAt(new \DateTimeImmutable());
        $em->persist($set);

        $link = new SetListSet();
        $link->setSet($set);
        $link->setSetList($setList);
        $link->setShowImages(true);
        $link->setShowParts(true);
        $link->setShowMinifigs(true);
        $link->setComplete(false);
        $link->setInstructions(false);
        $em->persist($link);

        $em->flush();

        return $set;
    }

    // -------------------------------------------------------------------------
    // POST /api/lego/set-lists/{listId}/sets/{number}/add-images
    // -------------------------------------------------------------------------

    public function testAddImageToSetInBoardRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            'http://legolibrary-dev/api/lego/set-lists/some-list/sets/6285-1/add-images'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAddImageToSetInBoard(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        $setList = $this->getEntityManager()->getRepository(SetList::class)->findAll()[0];
        $this->createSetAndLinkToList($setList);

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $uploadedFilePath = sys_get_temp_dir() . '/test_set_image.jpg';
        copy(__DIR__ . '/../../fixtures/image.jpg', $uploadedFilePath);
        $file = new UploadedFile($uploadedFilePath, 'image.jpg', null, null, false);

        $listId = (string) $setList->getId();

        $response = $client->request(
            'POST',
            "http://legolibrary-dev/api/lego/set-lists/{$listId}/sets/6285-1/add-images",
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'extra'   => [
                    'files' => ['files' => [$file]],
                ],
            ]
        );

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $this->assertArrayHasKey('id', $json[0]);
        $this->assertArrayHasKey('contentUrl', $json[0]);
    }

    public function testAddImageReturns404WhenSetNotInList(): void
    {
        $options = [
            'times'    => 1,
            'password' => 'Gravity35#',
            'active'   => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        // Create a set list manually (no set linked to it)
        $em = $this->getEntityManager();
        $userData = $this->getEntityManager()->getRepository(User::class)->findAll()[0]->getUserData();

        $setList = new SetList();
        $setList->setTitle('Empty Board');
        $setList->setDescription('No sets linked');
        $setList->setPublic(true);
        $setList->setPublicationDate(new \DateTimeImmutable());
        $setList->setUserData($userData);
        $em->persist($setList);
        $em->flush();

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $uploadedFilePath = sys_get_temp_dir() . '/test_set_image2.jpg';
        copy(__DIR__ . '/../../fixtures/image.jpg', $uploadedFilePath);
        $file = new UploadedFile($uploadedFilePath, 'image.jpg', null, null, false);

        $listId = (string) $setList->getId();

        $response = $client->request(
            'POST',
            "http://legolibrary-dev/api/lego/set-lists/{$listId}/sets/6285-1/add-images",
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'extra'   => [
                    'files' => ['files' => [$file]],
                ],
            ]
        );

        // Set doesn't exist in this list — expect 4xx
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function testAddImageWithNoFileReturns400(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);

        $setList = $this->getEntityManager()->getRepository(SetList::class)->findAll()[0];
        $this->createSetAndLinkToList($setList);

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $listId = (string) $setList->getId();

        $response = $client->request(
            'POST',
            "http://legolibrary-dev/api/lego/set-lists/{$listId}/sets/6285-1/add-images",
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
