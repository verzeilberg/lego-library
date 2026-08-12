<?php

namespace App\Tests;

use App\Entity\Lego\Color;
use App\Entity\Lego\Minifig;
use App\Entity\Lego\Part;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\SetPart;
use App\Entity\Lego\SetRating;
use App\Entity\Lego\Theme;
use App\Entity\Lego\UserSetPart;
use App\Entity\User\User;
use App\Entity\User\UserData;

class FullDataFixturesTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFullFixtures();
    }

    public function testThemesAreCreated(): void
    {
        $themes = $this->getEntityManager()->getRepository(Theme::class)->findAll();
        $this->assertCount(3, $themes);
        $names = array_map(fn(Theme $t) => $t->getName(), $themes);
        $this->assertContains('Star Wars', $names);
        $this->assertContains('City', $names);
        $this->assertContains('Technic', $names);
    }

    public function testColorsAreCreated(): void
    {
        $colors = $this->getEntityManager()->getRepository(Color::class)->findAll();
        $this->assertCount(4, $colors);
    }

    public function testPartsAreCreated(): void
    {
        $parts = $this->getEntityManager()->getRepository(Part::class)->findAll();
        $this->assertCount(3, $parts);
    }

    public function testSetsAreCreated(): void
    {
        $sets = $this->getEntityManager()->getRepository(Set::class)->findAll();
        $this->assertCount(3, $sets);

        $numbers = array_map(fn(Set $s) => $s->getNumber(), $sets);
        $this->assertContains('75192-1', $numbers);
        $this->assertContains('60316-1', $numbers);
        $this->assertContains('42143-1', $numbers);
    }

    public function testSetPartsAreCreated(): void
    {
        $parts = $this->getEntityManager()->getRepository(SetPart::class)->findAll();
        $this->assertCount(5, $parts);
    }

    public function testMinifigsAreCreated(): void
    {
        $minifigs = $this->getEntityManager()->getRepository(Minifig::class)->findAll();
        $this->assertCount(3, $minifigs);

        $names = array_map(fn(Minifig $m) => $m->getName(), $minifigs);
        $this->assertContains('Han Solo', $names);
        $this->assertContains('Chewbacca', $names);
        $this->assertContains('Police Officer', $names);
    }

    public function testUsersAreCreated(): void
    {
        $users = $this->getEntityManager()->getRepository(User::class)->findAll();
        $this->assertCount(3, $users);

        $activeUsers = array_filter($users, fn(User $u) => $u->isActive());
        $this->assertCount(2, $activeUsers);
    }

    public function testUserDataIsCreated(): void
    {
        $allData = $this->getEntityManager()->getRepository(UserData::class)->findAll();
        $this->assertCount(3, $allData);
    }

    public function testSetListsAreCreated(): void
    {
        $lists = $this->getEntityManager()->getRepository(SetList::class)->findAll();
        $this->assertCount(40, $lists);

        $publicLists = array_filter($lists, fn(SetList $l) => $l->isPublic());
        $this->assertCount(31, $publicLists);
    }

    public function testChildSetListExists(): void
    {
        $allLists = $this->getEntityManager()->getRepository(SetList::class)->findAll();
        $childLists = array_filter($allLists, fn(SetList $l) => $l->getParentList() !== null);
        $this->assertCount(1, $childLists);
        $this->assertEquals('Episode IV Sets', array_values($childLists)[0]->getTitle());
    }

    public function testSetListSetsAreCreated(): void
    {
        $entries = $this->getEntityManager()->getRepository(SetListSet::class)->findAll();
        $this->assertCount(4, $entries);
    }

    public function testSetRatingsAreCreated(): void
    {
        $ratings = $this->getEntityManager()->getRepository(SetRating::class)->findAll();
        $this->assertCount(5, $ratings);

        $values = array_map(fn(SetRating $r) => $r->getValue(), $ratings);
        $this->assertContains(5, $values);
        $this->assertContains(3, $values);
    }

    public function testUserSetPartsAreCreated(): void
    {
        $defects = $this->getEntityManager()->getRepository(UserSetPart::class)->findAll();
        $this->assertCount(3, $defects);
    }

    public function testInactiveUserCannotLogin(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email'    => 'inactive@example.com',
                'password' => 'Password3#',
            ],
        ]);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testActiveUserCanLogin(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email'    => 'john.doe@example.com',
                'password' => 'Password1#',
            ],
        ]);

        $json = $response->toArray();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('token', $json);
    }
}
