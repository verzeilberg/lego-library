<?php

namespace App\Tests;

use App\Entity\Lego\SetList;
use App\Entity\User\Friendship;
use App\Enum\BoardVisibility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Board visibility (public / friends / private) and sharing a board with a friend.
 *
 * Fixtures: john owns "My Star Wars Collection" (with sub-board "Episode IV Sets")
 * and the private "Wishlist"; jane owns "City Collection".
 */
class BoardSharingTest extends BaseTest
{
    private function login(string $email, string $password): array
    {
        $client = self::createClient();
        $response = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => ['email' => $email, 'password' => $password],
        ]);
        return [$client, $response->toArray()['token']];
    }

    /** The current kernel's entity manager (fixtures and clients reboot the kernel). */
    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    private function board(string $title): SetList
    {
        return $this->em()->getRepository(SetList::class)->findOneBy(['title' => $title]);
    }

    private function makeJohnAndJaneFriends(): void
    {
        $em = $this->em();
        $friendship = (new Friendship())
            ->setRequester($this->board('My Star Wars Collection')->getUserData())
            ->setRecipient($this->board('City Collection')->getUserData())
            ->setStatus('accepted');
        $em->persist($friendship);
        $em->flush();
    }

    private function shareStarWarsWithJane(): void
    {
        $em = $this->em();
        $starWars = $this->board('My Star Wars Collection');
        $starWars->addSharedWith($this->board('City Collection')->getUserData());
        $em->flush();
    }

    private function postBoard($client, string $token, array $parameters): void
    {
        $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'extra'   => ['parameters' => $parameters],
        ]);
    }

    public function testFriendsOnlyBoardIsVisibleToFriendsButNotToOthers(): void
    {
        $this->loadFullFixtures();
        $em = $this->em();
        $wishlist = $this->board('Wishlist');
        $wishlist->setVisibility(BoardVisibility::Friends);
        $em->flush();
        $wishlistId = $wishlist->getId()->toString();

        [$client, $token] = $this->login('jane.smith@example.com', 'Password2#');
        $client->request('GET', 'http://legolibrary-dev/api/set-list/' . $wishlistId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->makeJohnAndJaneFriends();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-list/' . $wishlistId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame('friends', $data['visibility']);
        $this->assertFalse($data['canEdit']);
        $this->assertFalse($data['canContribute']);
    }

    public function testPrivateBoardIsHiddenFromFriends(): void
    {
        $this->loadFullFixtures();
        $this->makeJohnAndJaneFriends();
        $wishlistId = $this->board('Wishlist')->getId()->toString();

        [$client, $token] = $this->login('jane.smith@example.com', 'Password2#');
        $client->request('GET', 'http://legolibrary-dev/api/set-lists/' . $wishlistId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreatingABoardStoresItsVisibility(): void
    {
        $this->loadFullFixtures();

        [$client, $token] = $this->login('john.doe@example.com', 'Password1#');
        $this->postBoard($client, $token, ['title' => 'Friends Only', 'visibility' => 'friends']);
        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $this->assertSame(BoardVisibility::Friends, $this->board('Friends Only')->getVisibility());
    }

    public function testSharedFriendCanAddBoardsAnywhereInsideTheSharedBoard(): void
    {
        $this->loadFullFixtures();
        $this->makeJohnAndJaneFriends();
        $this->shareStarWarsWithJane();
        $episodeIvId = $this->board('Episode IV Sets')->getId()->toString();

        [$client, $token] = $this->login('jane.smith@example.com', 'Password2#');
        $this->postBoard($client, $token, ['title' => 'Jane Added This', 'parentId' => $episodeIvId]);
        $this->assertResponseIsSuccessful();

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-list/' . $episodeIvId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($response->toArray()['canContribute']);
        $this->assertTrue($response->toArray()['canEdit']);
    }

    public function testSharedFriendCannotEditTheSharedBoardItself(): void
    {
        $this->loadFullFixtures();
        $this->makeJohnAndJaneFriends();
        $this->shareStarWarsWithJane();
        $starWarsId = $this->board('My Star Wars Collection')->getId()->toString();
        $episodeIvId = $this->board('Episode IV Sets')->getId()->toString();

        [$client, $token] = $this->login('jane.smith@example.com', 'Password2#');

        $this->postBoard($client, $token, ['id' => $starWarsId, 'title' => 'Hijacked']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('DELETE', 'http://legolibrary-dev/api/set-list/delete/' . $starWarsId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Boards inside the shared board can be edited
        $this->postBoard($client, $token, ['id' => $episodeIvId, 'title' => 'Episode IV (renamed)', 'visibility' => 'public']);
        $this->assertResponseIsSuccessful();
    }

    public function testOnlyTheOwnerCanShareAndOnlyWithFriends(): void
    {
        $this->loadFullFixtures();
        $starWars = $this->board('My Star Wars Collection');
        $starWarsId = $starWars->getId()->toString();
        $janeId = $this->board('City Collection')->getUserData()->getId();
        $johnId = $starWars->getUserData()->getId();

        [$johnClient, $johnToken] = $this->login('john.doe@example.com', 'Password1#');
        $johnClient->request('POST', "http://legolibrary-dev/api/set-list/$starWarsId/share/$janeId", [
            'headers' => ['Authorization' => 'Bearer ' . $johnToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->makeJohnAndJaneFriends();
        $johnClient->request('POST', "http://legolibrary-dev/api/set-list/$starWarsId/share/$janeId", [
            'headers' => ['Authorization' => 'Bearer ' . $johnToken],
        ]);
        $this->assertResponseIsSuccessful();

        [$janeClient, $janeToken] = $this->login('jane.smith@example.com', 'Password2#');
        $janeClient->request('POST', "http://legolibrary-dev/api/set-list/$starWarsId/unshare/$johnId", [
            'headers' => ['Authorization' => 'Bearer ' . $janeToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // ...but Jane can remove herself
        $janeClient->request('POST', "http://legolibrary-dev/api/set-list/$starWarsId/unshare/$janeId", [
            'headers' => ['Authorization' => 'Bearer ' . $janeToken],
        ]);
        $this->assertResponseIsSuccessful();
    }
}
