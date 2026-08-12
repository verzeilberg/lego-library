<?php

namespace App\DataFixtures;

use App\Entity\Lego\Color;
use App\Entity\Lego\Minifig;
use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\SetMinifig;
use App\Entity\Lego\SetPart;
use App\Entity\Lego\SetRating;
use App\Entity\Lego\Theme;
use App\Entity\Lego\UserSetPart;
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

        // ------------------------------------------------------------------ //
        // 3. LEGO THEMES, COLORS, PARTS, SETS, MINIFIGS, RATINGS, USER SET PARTS
        // ------------------------------------------------------------------ //

        // 3.1 THEMES
        $themeStarWars = new Theme();
        $themeStarWars->setThemeId(180);
        $themeStarWars->setName('Star Wars');
        $themeStarWars->setParentThemeId(null);

        $themeCity = new Theme();
        $themeCity->setThemeId(114);
        $themeCity->setName('City');
        $themeCity->setParentThemeId(null);

        $themeTechnic = new Theme();
        $themeTechnic->setThemeId(187);
        $themeTechnic->setName('Technic');
        $themeTechnic->setParentThemeId(null);

        $manager->persist($themeStarWars);
        $manager->persist($themeCity);
        $manager->persist($themeTechnic);

        // 3.2 COLORS
        $colorBlack = new Color();
        $colorBlack->setId(26);
        $colorBlack->setName('Black');
        $colorBlack->setRgb('05131D');
        $colorBlack->setIsTrans(false);

        $colorWhite = new Color();
        $colorWhite->setId(1);
        $colorWhite->setName('White');
        $colorWhite->setRgb('FFFFFF');
        $colorWhite->setIsTrans(false);

        $colorRed = new Color();
        $colorRed->setId(4);
        $colorRed->setName('Red');
        $colorRed->setRgb('C41E3A');
        $colorRed->setIsTrans(false);

        $colorBlue = new Color();
        $colorBlue->setId(5);
        $colorBlue->setName('Blue');
        $colorBlue->setRgb('0055BF');
        $colorBlue->setIsTrans(false);

        $manager->persist($colorBlack);
        $manager->persist($colorWhite);
        $manager->persist($colorRed);
        $manager->persist($colorBlue);

        // 3.3 PARTS + PART COLORS
        $partBrick2x4 = new Part();
        $partBrick2x4->setPartNumber('3001');
        $partBrick2x4->setName('Brick 2 x 4');

        $partBrick2x2 = new Part();
        $partBrick2x2->setPartNumber('3003');
        $partBrick2x2->setName('Brick 2 x 2');

        $partPlate1x2 = new Part();
        $partPlate1x2->setPartNumber('3023');
        $partPlate1x2->setName('Plate 1 x 2');

        $manager->persist($partBrick2x4);
        $manager->persist($partBrick2x2);
        $manager->persist($partPlate1x2);
        $manager->flush();

        $partColorBlack2x4 = new PartColor();
        $partColorBlack2x4->setPart($partBrick2x4);
        $partColorBlack2x4->setColor($colorBlack);

        $partColorWhite2x4 = new PartColor();
        $partColorWhite2x4->setPart($partBrick2x4);
        $partColorWhite2x4->setColor($colorWhite);

        $partColorRed2x2 = new PartColor();
        $partColorRed2x2->setPart($partBrick2x2);
        $partColorRed2x2->setColor($colorRed);

        $partColorBluePlate = new PartColor();
        $partColorBluePlate->setPart($partPlate1x2);
        $partColorBluePlate->setColor($colorBlue);

        $manager->persist($partColorBlack2x4);
        $manager->persist($partColorWhite2x4);
        $manager->persist($partColorRed2x2);
        $manager->persist($partColorBluePlate);

        // 3.4 SETS
        $setFalcon = new Set();
        $setFalcon->setNumber('75192-1');
        $setFalcon->setBaseNumber('75192');
        $setFalcon->setName('Millennium Falcon');
        $setFalcon->setYear(2017);
        $setFalcon->setNumParts(7541);
        $setFalcon->setTotalParts(7541);
        $setFalcon->setTotalPartsQuantity(7541);
        $setFalcon->setTotalMiniFigParts(0);
        $setFalcon->setRating(5.0);
        $setFalcon->setTheme($themeStarWars);

        $setFireStation = new Set();
        $setFireStation->setNumber('60316-1');
        $setFireStation->setBaseNumber('60316');
        $setFireStation->setName('Fire Station');
        $setFireStation->setYear(2022);
        $setFireStation->setNumParts(509);
        $setFireStation->setTotalParts(509);
        $setFireStation->setTotalPartsQuantity(509);
        $setFireStation->setTotalMiniFigParts(0);
        $setFireStation->setRating(4.5);
        $setFireStation->setTheme($themeCity);

        $setBugatti = new Set();
        $setBugatti->setNumber('42143-1');
        $setBugatti->setBaseNumber('42143');
        $setBugatti->setName('Ferrari Daytona SP3');
        $setBugatti->setYear(2022);
        $setBugatti->setNumParts(3778);
        $setBugatti->setTotalParts(3778);
        $setBugatti->setTotalPartsQuantity(3778);
        $setBugatti->setTotalMiniFigParts(0);
        $setBugatti->setRating(4.8);
        $setBugatti->setTheme($themeTechnic);

        $manager->persist($setFalcon);
        $manager->persist($setFireStation);
        $manager->persist($setBugatti);
        $manager->flush();

        // 3.5 SET PARTS (5 parts across sets)
        $setPart1 = new SetPart();
        $setPart1->setModel($setFalcon);
        $setPart1->setPartColor($partColorBlack2x4);
        $setPart1->setQuantity(100);

        $setPart2 = new SetPart();
        $setPart2->setModel($setFalcon);
        $setPart2->setPartColor($partColorWhite2x4);
        $setPart2->setQuantity(50);

        $setPart3 = new SetPart();
        $setPart3->setModel($setFireStation);
        $setPart3->setPartColor($partColorRed2x2);
        $setPart3->setQuantity(200);

        $setPart4 = new SetPart();
        $setPart4->setModel($setFireStation);
        $setPart4->setPartColor($partColorBluePlate);
        $setPart4->setQuantity(100);

        $setPart5 = new SetPart();
        $setPart5->setModel($setBugatti);
        $setPart5->setPartColor($partColorBlack2x4);
        $setPart5->setQuantity(300);

        $manager->persist($setPart1);
        $manager->persist($setPart2);
        $manager->persist($setPart3);
        $manager->persist($setPart4);
        $manager->persist($setPart5);

        // 3.6 MINIFIGS
        $minifigHanSolo = new Minifig();
        $minifigHanSolo->setId(1);
        $minifigHanSolo->setSetNumId('75192-1-han');
        $minifigHanSolo->setName('Han Solo');

        $minifigChewbacca = new Minifig();
        $minifigChewbacca->setId(2);
        $minifigChewbacca->setSetNumId('75192-1-chewie');
        $minifigChewbacca->setName('Chewbacca');

        $minifigPoliceOfficer = new Minifig();
        $minifigPoliceOfficer->setId(3);
        $minifigPoliceOfficer->setSetNumId('60316-1-police');
        $minifigPoliceOfficer->setName('Police Officer');

        $manager->persist($minifigHanSolo);
        $manager->persist($minifigChewbacca);
        $manager->persist($minifigPoliceOfficer);
        $manager->flush();

        // 3.7 SET MINIFIGS (linking minifigs to sets)
        $setMinifig1 = new SetMinifig();
        $setMinifig1->setSet($setFalcon);
        $setMinifig1->setMinifig($minifigHanSolo);
        $setMinifig1->setQuantity(1);

        $setMinifig2 = new SetMinifig();
        $setMinifig2->setSet($setFalcon);
        $setMinifig2->setMinifig($minifigChewbacca);
        $setMinifig2->setQuantity(1);

        $setMinifig3 = new SetMinifig();
        $setMinifig3->setSet($setFireStation);
        $setMinifig3->setMinifig($minifigPoliceOfficer);
        $setMinifig3->setQuantity(1);

        $manager->persist($setMinifig1);
        $manager->persist($setMinifig2);
        $manager->persist($setMinifig3);
        $manager->flush();

        // 3.8 SET LIST SETS (link sets to set lists)
        $sls1 = new SetListSet();
        $sls1->setSet($setFalcon);
        $sls1->setSetList($publicList1);
        $sls1->setShowImages(true);
        $sls1->setShowParts(true);
        $sls1->setShowMinifigs(true);
        $sls1->setComplete(true);
        $sls1->setInstructions(true);

        $sls2 = new SetListSet();
        $sls2->setSet($setFireStation);
        $sls2->setSetList($publicList2);
        $sls2->setShowImages(true);
        $sls2->setShowParts(true);
        $sls2->setShowMinifigs(true);
        $sls2->setComplete(true);
        $sls2->setInstructions(true);

        $sls3 = new SetListSet();
        $sls3->setSet($setBugatti);
        $sls3->setSetList($childList1);
        $sls3->setShowImages(true);
        $sls3->setShowParts(true);
        $sls3->setShowMinifigs(true);
        $sls3->setComplete(true);
        $sls3->setInstructions(true);

        $sls4 = new SetListSet();
        $sls4->setSet($setFalcon);
        $sls4->setSetList($childList1);
        $sls4->setShowImages(true);
        $sls4->setShowParts(true);
        $sls4->setShowMinifigs(true);
        $sls4->setComplete(true);
        $sls4->setInstructions(true);

        $manager->persist($sls1);
        $manager->persist($sls2);
        $manager->persist($sls3);
        $manager->persist($sls4);
        $manager->flush();

        // 3.9 SET RATINGS
        $rating1 = new SetRating();
        $rating1->setUser($userData1);
        $rating1->setSet($setFalcon);
        $rating1->setValue(5);

        $rating2 = new SetRating();
        $rating2->setUser($userData1);
        $rating2->setSet($setFireStation);
        $rating2->setValue(4);

        $rating3 = new SetRating();
        $rating3->setUser($userData2);
        $rating3->setSet($setBugatti);
        $rating3->setValue(5);

        $rating4 = new SetRating();
        $rating4->setUser($userData2);
        $rating4->setSet($setFalcon);
        $rating4->setValue(4);

        $rating5 = new SetRating();
        $rating5->setUser($userData1);
        $rating5->setSet($setFireStation);
        $rating5->setValue(3);

        $manager->persist($rating1);
        $manager->persist($rating2);
        $manager->persist($rating3);
        $manager->persist($rating4);
        $manager->persist($rating5);

        // 3.10 USER SET PARTS (defect tracking)
        $usp1 = new UserSetPart();
        $usp1->setSetListSet($sls1);
        $usp1->setSetPart($setPart1);
        $usp1->setMissingQuantity(0);
        $usp1->setDamagedQuantity(1);
        $usp1->setDiscolouredQuantity(0);

        $usp2 = new UserSetPart();
        $usp2->setSetListSet($sls2);
        $usp2->setSetPart($setPart3);
        $usp2->setMissingQuantity(2);
        $usp2->setDamagedQuantity(0);
        $usp2->setDiscolouredQuantity(1);

        $usp3 = new UserSetPart();
        $usp3->setSetListSet($sls3);
        $usp3->setSetPart($setPart5);
        $usp3->setMissingQuantity(0);
        $usp3->setDamagedQuantity(0);
        $usp3->setDiscolouredQuantity(2);

        $manager->persist($usp1);
        $manager->persist($usp2);
        $manager->persist($usp3);

        $manager->flush();
    }
}
