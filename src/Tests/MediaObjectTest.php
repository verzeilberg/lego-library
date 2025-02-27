<?php
// tests/MediaObjectTest.php

namespace App\Tests;

use App\Entity\MediaObject;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaObjectTest extends BaseTest
{

    public function testUploadImageToUserDateObject(): void
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

        //Copy file to temp folder
        $uploadedFilePath = sys_get_temp_dir() . '/test_image.jpg';
        copy(__DIR__ . '/../../fixtures/image.jpg', $uploadedFilePath);
        // The file "image.jpg" is the folder fixtures which is in the project dir
        $file = new UploadedFile($uploadedFilePath, 'image.jpg', null, null, false);

        $response = $client->request('POST', 'http://legolibrary-dev/api/user/media_objects', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'multipart/form-data'
            ],
            'extra' => [
                // If you have additional fields in your MediaObject entity, use the parameters.
                'parameters' => [
                    // 'title' => 'title'
                ],
                'files' => [
                    'file' => $file,
                ],
            ]
        ]);

        $json = $response->toArray();
        $this->assertArrayHasKey('profilePicture', $json);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(MediaObject::class);

    }
}
