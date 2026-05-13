<?php

namespace App\DataFixtures;

use App\Entity\Lego\SetList;
use App\Entity\User\User;
use App\Entity\User\UserData;
use App\Enum\Geslacht;
use App\Service\EmailEncryptionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FullDataFixtures extends Fixture
{
    public const LIST_STAR_WARS = 'list-star-wars';
    public const LIST_EPISODE_IV = 'list-episode-iv';
    public const LIST_WISHLIST   = 'list-wishlist';
    public const LIST_CITY       = 'list-city';

    private string $uploadDir;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EmailEncryptionService      $emailEncryptionService,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        $this->uploadDir = $projectDir . '/public/media/lego';
    }

    private function createPlaceholderImage(string $label): string
    {
        $filename = 'fixture-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($label)) . '.jpg';
        $path = $this->uploadDir . '/' . $filename;

        if (!file_exists($path)) {
            $escaped = escapeshellarg($path);
            $text    = escapeshellarg($label);
            exec("python3 -c \"
from PIL import Image, ImageDraw
img = Image.new('RGB', (1600, 800), color=(30, 30, 80))
draw = ImageDraw.Draw(img)
draw.text((50, 380), $text, fill=(255, 255, 255))
img.save($escaped, 'JPEG', quality=85)
\"");
        }

        return $filename;
    }

    public function load(ObjectManager $manager): void
    {
        foreach (['lego', 'profiel'] as $folder) {
            $dir = $this->uploadDir . '/../' . $folder;
            foreach (glob($dir . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // ------------------------------------------------------------------ //
        // 1. USERS + USER DATA
        // ------------------------------------------------------------------ //

        $user1 = new User();
        $user1->setEmail('john.doe@example.com');
        $user1->setEmailHash($this->emailEncryptionService->hash('john.doe@example.com'));
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'Password1#'));
        $user1->setActive(true);

        $userData1 = new UserData();
        $userData1->setFirstName('John');
        $userData1->setLastName('Doe');
        $userData1->setUserName('johndoe');
        $userData1->setBio('LEGO enthusiast since 1995. Star Wars fan.');
        $userData1->setGeslacht(Geslacht::Man);
        $userData1->setOwner($user1);

        $manager->persist($user1);
        $manager->persist($userData1);

        $user2 = new User();
        $user2->setEmail('jane.smith@example.com');
        $user2->setEmailHash($this->emailEncryptionService->hash('jane.smith@example.com'));
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'Password2#'));
        $user2->setActive(true);

        $userData2 = new UserData();
        $userData2->setFirstName('Jane');
        $userData2->setLastName('Smith');
        $userData2->setUserName('janesmith');
        $userData2->setBio('City and Technic collector.');
        $userData2->setGeslacht(Geslacht::Vrouw);
        $userData2->setOwner($user2);

        $manager->persist($user2);
        $manager->persist($userData2);

        $user3 = new User();
        $user3->setEmail('inactive@example.com');
        $user3->setEmailHash($this->emailEncryptionService->hash('inactive@example.com'));
        $user3->setPassword($this->passwordHasher->hashPassword($user3, 'Password3#'));
        $user3->setActive(false);

        $userData3 = new UserData();
        $userData3->setFirstName('Inactive');
        $userData3->setLastName('User');
        $userData3->setOwner($user3);

        $manager->persist($user3);
        $manager->persist($userData3);
        $manager->flush();

        // ------------------------------------------------------------------ //
        // 2. SET LISTS
        // ------------------------------------------------------------------ //

        $publicList1 = new SetList();
        $publicList1->setTitle('My Star Wars Collection');
        $publicList1->setDescription('All my Star Wars sets, displayed with pride.');
        $publicList1->setPublic(true);
        $publicList1->setPublicationDate(new \DateTimeImmutable('-10 days'));
        $publicList1->setUserData($userData1);
        $publicList1->setFilePath($this->createPlaceholderImage('Star Wars Collection'));

        $childList1 = new SetList();
        $childList1->setTitle('Episode IV Sets');
        $childList1->setDescription('Sets from A New Hope.');
        $childList1->setPublic(true);
        $childList1->setPublicationDate(new \DateTimeImmutable('-5 days'));
        $childList1->setParentList($publicList1);
        $childList1->setUserData($userData1);
        $childList1->setFilePath($this->createPlaceholderImage('Episode IV Sets'));

        $privateList1 = new SetList();
        $privateList1->setTitle('Wishlist');
        $privateList1->setDescription('Sets I still want to buy.');
        $privateList1->setPublic(false);
        $privateList1->setPublicationDate(new \DateTimeImmutable('-3 days'));
        $privateList1->setUserData($userData1);
        $privateList1->setFilePath($this->createPlaceholderImage('Wishlist'));

        $publicList2 = new SetList();
        $publicList2->setTitle('City Collection');
        $publicList2->setDescription('My city and police themed sets.');
        $publicList2->setPublic(true);
        $publicList2->setPublicationDate(new \DateTimeImmutable('-7 days'));
        $publicList2->setUserData($userData2);
        $publicList2->setFilePath($this->createPlaceholderImage('City Collection'));

        $manager->persist($publicList1);
        $manager->persist($childList1);
        $manager->persist($privateList1);
        $manager->persist($publicList2);

        $this->addReference(self::LIST_STAR_WARS, $publicList1);
        $this->addReference(self::LIST_EPISODE_IV, $childList1);
        $this->addReference(self::LIST_WISHLIST, $privateList1);
        $this->addReference(self::LIST_CITY, $publicList2);

        // Extra boards for John (userData1)
        $extraBoardsUser1 = [
            ['Technic Masterpieces',        'High-end Technic builds.',                        true],
            ['Classic Space Collection',    'All the grey and blue sets from the 80s.',        true],
            ['Ninjago Season 1',            'The first season ninja sets.',                     true],
            ['Harry Potter Sets',           'Hogwarts and beyond.',                             true],
            ['Ideas Sets',                  'Fan-designed LEGO Ideas builds.',                  true],
            ['Architecture Collection',     'Skylines and landmarks.',                          true],
            ['Jurassic World',              'Dinosaurs and theme park chaos.',                  true],
            ['Marvel Super Heroes',         'Avengers, Spider-Man and more.',                   true],
            ['DC Comics',                   'Batman, Superman and the Justice League.',          false],
            ['Seasonal Sets',               'Christmas, Halloween and Easter exclusives.',       false],
            ['Creator 3-in-1',              'Versatile sets that build three different models.', true],
            ['Speed Champions',             '1:18 scale cars from top manufacturers.',           true],
            ['Minecraft Collection',        'Pixelated LEGO landscapes.',                       true],
            ['Hidden Side',                 'Augmented-reality ghost-hunting sets.',             false],
            ['BrickHeadz',                  'Collectible character busts.',                     true],
            ['Icons & Exclusives',          'Large display models and limited editions.',        true],
            ['Duplo & Juniors',             'Sets shared with younger family members.',          false],
        ];

        foreach ($extraBoardsUser1 as $i => [$title, $desc, $public]) {
            $list = new SetList();
            $list->setTitle($title);
            $list->setDescription($desc);
            $list->setPublic($public);
            $list->setPublicationDate(new \DateTimeImmutable('-' . ($i + 1) . ' days'));
            $list->setUserData($userData1);
            $manager->persist($list);
        }

        // Extra boards for Jane (userData2)
        $extraBoardsUser2 = [
            ['Police & Fire',               'Emergency services sets.',                         true],
            ['City Trains',                 'Train stations and cargo sets.',                   true],
            ['City Airport',                'Planes, helicopters and airports.',                true],
            ['City Harbour',                'Ships and harbour infrastructure.',                true],
            ['Modular Buildings',           'Detailed modular city buildings.',                 true],
            ['Friends Collection',          'Heartlake City and beyond.',                       true],
            ['Elves & Fairies',             'Fantasy-themed adventure sets.',                   false],
            ['Monkie Kid',                  'Chinese mythology-inspired sets.',                 true],
            ['DREAMZzz',                    'Dream world adventure sets.',                      true],
            ['Botanical Collection',        'Flowers, plants and nature kits.',                 true],
            ['Art Sets',                    'Large mosaic and framed artwork builds.',           true],
            ['Disney Princess',             'Fairy-tale castles and characters.',               true],
            ['Gabby\'s Dollhouse',          'Cute cat-themed sets.',                            false],
            ['Technic Cars',                'Functional model vehicles.',                       true],
            ['Control+ Powered Up',         'App-controlled motorised builds.',                 false],
            ['Forma',                       'Kinetic art sets.',                                false],
            ['Vidiyo',                      'Music video maker sets.',                          true],
            ['Overwatch',                   'Game-inspired hero sets.',                         true],
            ['Stranger Things',             'The Upside Down set and more.',                    true],
        ];

        foreach ($extraBoardsUser2 as $i => [$title, $desc, $public]) {
            $list = new SetList();
            $list->setTitle($title);
            $list->setDescription($desc);
            $list->setPublic($public);
            $list->setPublicationDate(new \DateTimeImmutable('-' . ($i + 2) . ' days'));
            $list->setUserData($userData2);
            $manager->persist($list);
        }

        $manager->flush();
    }
}
