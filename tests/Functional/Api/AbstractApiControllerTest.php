<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApiControllerTest extends WebTestCase
{
    /** @var KernelBrowser */
    protected $client;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Set authenticated request.
     *
     * @param string $method
     * @param string $url
     */
    protected function setAuthRequest(string $method, string $url): void
    {
        $this->client->request($method, $url, [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
        ]);
    }
}
