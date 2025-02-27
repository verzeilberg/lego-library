<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
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
     * Loads and executes database fixtures.
     *
     * This method initializes user fixtures with the provided options, adds them to the loader,
     * and purges the database before executing the loaded fixtures.
     *
     * @param array $options An array of options to configure the fixtures.
     * @throws Exception
     */
    protected function loadFixtures(array $options = []): void
    {

        self::bootKernel();
        $container = self::$kernel->getContainer();

        $userFixtures = $container->get(UserFixtures::class);
        $userFixtures->setOptions($options);
        $loader = new Loader();
        $loader->addFixture($userFixtures);

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);

        $executor->purge(); // Maak eerst de database schoon
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
