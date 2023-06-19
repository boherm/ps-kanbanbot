<?php

declare(strict_types=1);

namespace App\Tests\Shared\Webhook;

use App\PullRequest\Application\Command\AddLabelByApprovalCountCommand;
use App\PullRequest\Application\Command\RequestChangesCommand;
use App\PullRequestDashboard\Application\Command\MovePullRequestCardToColumnByApprovalCountCommand;
use App\PullRequestDashboard\Application\Command\MovePullRequestCardToColumnByLabelCommand;
use App\Shared\Adapter\SpyMessageBus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Messenger\MessageBusInterface;

class GithubWebhookTest extends WebTestCase
{
    private SpyMessageBus $commandBus;
    private AbstractBrowser $client;

    private function getPayloadReformated(string $payload): string
    {
        /** @var string $payloadReformated */
        $payloadReformated = json_encode(json_decode($payload, true));

        return $payloadReformated;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        /** @var SpyMessageBus $commandBus */
        $commandBus = self::$kernel->getContainer()->get(MessageBusInterface::class);
        $this->commandBus = $commandBus;
    }

    /**
     * @dataProvider successfulExecutionProvider
     *
     * @param object[] $expectedDispatchedMessages
     */
    public function testWebhook(string $eventType, string $payload, array $expectedDispatchedMessages): void
    {
        $this->client->request('POST', '/webhook/github', server: [
            'HTTP_X-GitHub-Event' => $eventType,
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $this->getPayloadReformated($payload), $_ENV['WEBHOOK_SECRET']),
        ], content: $payload);

        $this->assertResponseIsSuccessful();
        $this->assertEquals($expectedDispatchedMessages, $this->commandBus->getDispatchedMessages());
    }

    /**
     * @return array<array{string, string, object[]}>
     */
    public static function successfulExecutionProvider(): array
    {
        return [
            [
                'pull_request_review',
                '{
                    "action": "submitted",
                    "pull_request": {
                        "base": {
                            "repo": {
                                "name": "repo",
                                "owner": {
                                    "login": "owner"
                                }
                            }
                        },
                        "number": 123
                    },
                   "review": {
                      "state": "changes_requested"
                    }
                }',
                [
                    new RequestChangesCommand(repositoryOwner: 'owner', repositoryName: 'repo', pullRequestNumber: '123'),
                ],
            ],
            [
                'pull_request_review',
                '{
                  "action": "submitted",
                  "pull_request": {
                    "base": {
                      "repo": {
                        "name": "PrestaShop",
                        "owner": {
                          "login": "PrestaShop"
                        }
                      }
                    },
                    "number": 162
                  },
                  "review": {
                    "state": "approved"
                  }
                }',
                [
                    new MovePullRequestCardToColumnByApprovalCountCommand(
                        projectNumber: '17',
                        repositoryOwner: 'PrestaShop',
                        repositoryName: 'PrestaShop',
                        pullRequestNumber: '162'
                    ),
                    new AddLabelByApprovalCountCommand(
                        repositoryOwner: 'PrestaShop',
                        repositoryName: 'PrestaShop',
                        pullRequestNumber: '162'
                    ),
                ],
            ],
            ['pull_request_review', '{"action": ""}', []],
            ['pull_request_review', '{"action": "submitted", "review": {"state": ""}}', []],
            'Not supported event' => ['not_supported_event_type', '{"action": "not_supported_action"}', []],
            ['pull_request', '{"action": ""}', []],
            [
                'pull_request',
                '{
                    "action": "labeled",
                    "label": {
                        "name": "documentation"
                    },
                    "pull_request": {
                        "base": {
                            "repo": {
                                "name": "repo",
                                "owner": {
                                    "login": "owner"
                                }
                            }
                        },
                        "number": 123
                    }
                }',
                [
                    new MovePullRequestCardToColumnByLabelCommand(
                        projectNumber: '17',
                        repositoryOwner: 'owner',
                        repositoryName: 'repo',
                        pullRequestNumber: '123',
                        label: 'documentation',
                    ),
                ],
            ],
        ];
    }

    public function testInvalidJsonPayload(): void
    {
        $this->client->request(
            'POST',
            '/webhook/github',
            server: [
                'HTTP_X-GitHub-Event' => 'not_supported_event_type',
            ],
            content: '{"action"@: "not_supported_action"}'
        );
        $this->assertResponseStatusCodeSame(400);
        $this->assertSelectorTextContains('h2', 'JsonException JsonException BadRequestHttpException');
    }

    public function testErrorIfSignatureFromPayloadDoesntExist(): void
    {
        $this->client->request('POST', '/webhook/github', server: [
            'HTTP_X-GitHub-Event' => 'eventType',
        ], content: '{}');

        $this->assertResponseStatusCodeSame(406);
    }

    public function testErrorIfThePayloadSignatureIsInvalid(): void
    {
        $this->client->request('POST', '/webhook/github', server: [
            'HTTP_X-GitHub-Event' => 'eventType',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $this->getPayloadReformated('{}'), 'wrong_secret'),
        ], content: '{}');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider nothingHappenIfMethodIsDifferentThanPOSTProvider
     *
     * @param object[] $expectedDispatchedMessages
     */
    public function testNothingHappenIfMethodIsDifferentThanPOSTProvider(string $eventType, string $payload, array $expectedDispatchedMessages, string $method): void
    {
        $this->client->request($method, '/webhook/github', server: [
            'HTTP_X-GitHub-Event' => $eventType,
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $this->getPayloadReformated($payload), $_ENV['WEBHOOK_SECRET']),
        ], content: $payload);

        $this->assertResponseStatusCodeSame(406);
        $this->assertEquals($expectedDispatchedMessages, $this->commandBus->getDispatchedMessages());
    }

    /**
     * @return array<array{string, string, object[]}>
     */
    public static function nothingHappenIfMethodIsDifferentThanPOSTProvider(): array
    {
        return array_merge(
            array_map(
                fn (array $data) => [$data[0], $data[1], [], 'GET'],
                self::successfulExecutionProvider()
            ),
            array_map(
                fn (array $data) => [$data[0], $data[1], [], 'PUT'],
                self::successfulExecutionProvider()
            ),
            array_map(
                fn (array $data) => [$data[0], $data[1], [], 'PATCH'],
                self::successfulExecutionProvider()
            ),
            array_map(
                fn (array $data) => [$data[0], $data[1], [], 'DELETE'],
                self::successfulExecutionProvider()
            )
        );
    }
}
