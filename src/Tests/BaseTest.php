<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\FullDataFixtures;
use App\DataFixtures\UserFixtures;
use App\Doctrine\Subscriber\DoctrineTypeConfigurator;
use App\Service\TokenService;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseTest extends ApiTestCase
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;
    /** @var mixed|object|Container|ContainerInterface|null */
    private JWTTokenManagerInterface $jwtTokenManager;
    /** @var TokenService|mixed|object|Container|ContainerInterface|null */
    private TokenService $tokenService;

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->jwtTokenManager = self::getContainer()->get(JWTTokenManagerInterface::class);
        $this->tokenService = self::getContainer()->get(TokenService::class);

        self::getContainer()->get(DoctrineTypeConfigurator::class)->configure();

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->purge(); // Maak eerst de database schoon
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Loads UserFixtures with the given options.
     *
     * Available options:
     *   times      int      – number of users to create (default 10)
     *   password   string   – plain password to hash (default: random)
     *   active     bool     – whether users are activated (default false)
     *   userData   bool     – create UserData for each user (default true)
     *   userToken  bool     – create an activation token (default true)
     *   tokenType  int      – UserToken::TYPE_* constant (default activation)
     *   expiresAt  DateTime – token expiry (default +1 hour)
     *   modelList  bool     – create set lists for each user (default false)
     *
     * @throws Exception
     */
    protected function loadFixtures(array $options = []): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $userFixtures = $container->get(UserFixtures::class);
        $userFixtures->setOptions($options);
        $loader = new Loader();
        $loader->addFixture($userFixtures);

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);

        $executor->purge();
        $executor->execute($loader->getFixtures());
    }

    /**
     * Loads FullDataFixtures – a complete dataset covering all entity types:
     * themes, sets, parts, colors, minifigs, users, set lists, set list sets,
     * ratings, and defect-part tracking.
     *
     * Purges the database before loading.
     *
     * Credentials seeded:
     *   john.doe@example.com  / Password1#  (active)
     *   jane.smith@example.com / Password2#  (active)
     *   inactive@example.com  / Password3#  (NOT active)
     *
     * @throws Exception
     */
    protected function loadFullFixtures(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $fullFixtures = $container->get(FullDataFixtures::class);
        $loader = new Loader();
        $loader->addFixture($fullFixtures);

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);

        $executor->purge();
        $executor->execute($loader->getFixtures());
    }

    /**
     * Tears down the test environment.
     *
     * This method ensures that the parent tearDown process is executed and
     * closes the EntityManager to release any remaining resources.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
