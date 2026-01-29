<?php
// tests/MediaObjectTest.php

namespace App\Tests;

use App\Entity\Lego\SetList;
use App\Entity\Media\MediaObject;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ModelListTest extends BaseTest
{

    public function testAddModelList(): void
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

        $response = $client->request('POST', 'http://legolibrary-dev/api/set-list', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'multipart/form-data'
            ],
            'extra' => [
                // If you have additional fields in your MediaObject entity, use the parameters.
                'parameters' => [
                    'title' => 'Lego set list 1',
                    'description' => 'Lego set list 1 description',
                ],
                'files' => [
                    'file' => $file,
                ],
            ]
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('title', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertArrayHasKey('filePath', $json);
    }

    public function testGetModelListsForUser(): void
    {
        $client = self::createClient();

        $options = [
            'times' => 1,
            'password' => 'Gravity35#',
            'active' => true,
            'modelList' => true,
            'userData' => true,
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

        $response = $client->request('GET', 'http://legolibrary-dev/api/set-lists', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $modalList = $this->getEntityManager()
            ->getRepository(SetList::class)
            ->findAll();

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertEquals($json[0]['title'], $modalList[0]->getTitle());
        $this->assertEquals($json[0]['description'], $modalList[0]->getDescription());
    }
}
