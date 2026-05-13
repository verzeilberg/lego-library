<?php

namespace App\Tests;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\Theme;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests adding an existing set to a board without hitting the Rebrickable API.
 * When a set already exists in the database, SetService links it to the board
 * directly with no external calls.
 */
class AddSetToBoardTest extends BaseTest
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

    private function createSetInDatabase(): Set
    {
        $em = $this->getEntityManager();

        $theme = (new Theme())
            ->setThemeId(1)
            ->setName('Pirates');
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
        $em->flush();

        return $set;
    }

    // -------------------------------------------------------------------------
    // POST /api/lego/sets/create
    // -------------------------------------------------------------------------

    public function testAddSetToBoardRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => ['id' => 'some-id', 'legoNmbr' => '6285'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAddExistingSetToBoard(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);
        $this->createSetInDatabase();

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $setList   = $this->getEntityManager()->getRepository(SetList::class)->findAll();
        $setListId = (string) $setList[0]->getId();

        $response = $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'id'              => $setListId,
                'legoNmbr'        => '6285',
                'addLegoImages'   => false,
                'addLegoParts'    => false,
                'addLegoMinifigs' => false,
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertArrayHasKey('message', $json);
        $this->assertEquals('Set added to list successfully', $json['message']);
        $this->assertArrayHasKey('set', $json);
        $this->assertEquals('6285-1', $json['set']);
    }

    public function testAddSetToNonExistentBoardReturns404(): void
    {
        $options = [
            'times'    => 1,
            'password' => 'Gravity35#',
            'active'   => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);
        $this->createSetInDatabase();

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $response = $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'id'              => '00000000-0000-0000-0000-000000000000',
                'legoNmbr'        => '6285',
                'addLegoImages'   => false,
                'addLegoParts'    => false,
                'addLegoMinifigs' => false,
            ],
        ]);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testAddSetToBoardTwiceStillSucceeds(): void
    {
        $options = [
            'times'     => 1,
            'password'  => 'Gravity35#',
            'active'    => true,
            'userData'  => true,
            'modelList' => true,
        ];
        $this->loadFixtures($options);
        $this->createSetInDatabase();

        $user = $this->getEntityManager()->getRepository(User::class)->findAll();
        [$client, $token] = $this->loginAndGetToken($user[0]->getEmail(), 'Gravity35#');

        $setListId = (string) $this->getEntityManager()
            ->getRepository(SetList::class)
            ->findAll()[0]
            ->getId();

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'id'              => $setListId,
                'legoNmbr'        => '6285',
                'addLegoImages'   => false,
                'addLegoParts'    => false,
                'addLegoMinifigs' => false,
            ],
        ];

        // First add
        $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', $payload);
        $this->assertResponseIsSuccessful();

        // Second add — set already in list, should still return 200
        $response = $client->request('POST', 'http://legolibrary-dev/api/lego/sets/create', $payload);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
