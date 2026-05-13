<?php

namespace App\Tests;

use App\Entity\User\User;
use App\Entity\User\UserData;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests profile image upload via POST /api/user-data/edit.
 *
 * The old /api/user/media_objects endpoint no longer exists; MediaObject is
 * now used exclusively for set-list-set images.  Profile pictures are managed
 * through the UpdateUser controller.
 */
class MediaObjectTest extends BaseTest
{
    public function testUploadProfileImageToUserData(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'userData' => true,
        ];
        $this->loadFixtures($options);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findAll();

        $loginResponse = $client->request('POST', 'http://legolibrary-dev/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $user[0]->getEmail(),
                'password' => 'Gravity35#',
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        $uploadedFilePath = sys_get_temp_dir() . '/test_profile.jpg';
        copy(__DIR__ . '/../../fixtures/image.jpg', $uploadedFilePath);
        $file = new UploadedFile($uploadedFilePath, 'image.jpg', 'image/jpeg', null, true);

        $userData = $this->getEntityManager()
            ->getRepository(UserData::class)
            ->findAll();

        $response = $client->request('POST', 'http://legolibrary-dev/api/user-data/edit', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'extra' => [
                'parameters' => [
                    'firstName' => $userData[0]->getFirstName(),
                    'lastName'  => $userData[0]->getLastName(),
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $json = $response->toArray();
        $this->assertArrayHasKey('firstName', $json);
    }
}
