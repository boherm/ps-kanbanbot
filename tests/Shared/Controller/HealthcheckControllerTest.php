<?php

declare(strict_types=1);

namespace App\Tests\Shared\Controller;

use App\PullRequest\Infrastructure\Adapter\RestPullRequestRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthcheckControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * We test the endpoint with a success status.
     */
    public function testEndpointOk(): void
    {
        $container = $this->client->getContainer();

        // We mock the PullRequestRepository to avoid calling Github API
        // -> find method would be called once and work as if it was called without errors
        $pullRequestRepository = $this->getMockBuilder(RestPullRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $pullRequestRepository->method('find')->willReturn(null);
        $container->set(RestPullRequestRepository::class, $pullRequestRepository);
        $pullRequestRepository->expects($this->once())->method('find');

        // We query the endpoint
        $this->client->request('GET', '/healthcheck');
        $this->assertResponseIsSuccessful();
        $expectedResponse = [
            'status' => 'OK',
            'version' => $container->getParameter('app.version'),
            'github' => [
                'status' => 'up',
            ],
        ];
        $this->assertEquals(
            json_encode($expectedResponse),
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * We test the endpoint with a ko status.
     */
    public function testEndpointKo(): void
    {
        $container = $this->client->getContainer();

        // We mock the PullRequestRepository to avoid calling Github API
        // -> find method would be called once and work as if it was called with exception
        $pullRequestRepository = $this->getMockBuilder(RestPullRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $pullRequestRepository->method('find')
            ->willThrowException(new \Exception('Github API is down'));
        $container->set(RestPullRequestRepository::class, $pullRequestRepository);
        $pullRequestRepository->expects($this->once())->method('find');

        // We query the endpoint
        $this->client->request('GET', '/healthcheck');
        $this->assertResponseIsSuccessful();
        $expectedResponse = [
            'status' => 'KO',
            'version' => $container->getParameter('app.version'),
            'github' => [
                'status' => 'down',
            ],
        ];
        $this->assertEquals(
            json_encode($expectedResponse),
            $this->client->getResponse()->getContent()
        );
    }
}
