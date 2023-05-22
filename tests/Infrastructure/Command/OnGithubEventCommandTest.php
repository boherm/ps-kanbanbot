<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Command;

use App\Infrastructure\Adapter\SpyMessageBus;
use App\PullRequest\Application\Command\RequestChangesCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;

class OnGithubEventCommandTest extends KernelTestCase
{
    private SpyMessageBus $commandBus;
    private Filesystem $fs;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var SpyMessageBus $commandBus */
        $commandBus = self::bootKernel()->getContainer()->get(MessageBusInterface::class);
        $this->commandBus = $commandBus;
        $this->fs = new Filesystem();
        $this->commandTester = new CommandTester(
            (new Application(self::$kernel))->find('app:github:on-github-event')
        );
    }

    /**
     * @dataProvider successfulExecutionProvider
     *
     * @param object[] $expectedDispatchedMessages
     */
    public function testExecuteSuccessful(string $eventType, string $payload, array $expectedDispatchedMessages): void
    {
        /** @var string $testTempDir */
        $testTempDir = self::$kernel->getContainer()->getParameter('test_tmp_dir');
        $githubEventPayloadPathName = $testTempDir.'/github_event/test.json';
        $this->fs->dumpFile($githubEventPayloadPathName, $payload);

        $this->commandTester->execute([
            'event-type' => $eventType,
            'event-path-name' => $githubEventPayloadPathName,
        ]);

        $this->assertEquals($expectedDispatchedMessages, $this->commandBus->getDispatchedMessages());
        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Github event was handled with success!', $this->commandTester->getDisplay());

        $this->fs->remove($githubEventPayloadPathName);
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
	}
}',
            [
                new RequestChangesCommand(repositoryOwner: 'owner', repositoryName: 'repo', pullRequestNumber: '123'),
            ],
            ],
        ];
    }
}
