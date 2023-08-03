<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Command;

use App\Shared\Infrastructure\Adapter\SpyMessageBus;
use App\Tests\Shared\Infrastructure\Webhook\GithubWebhookTest;
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
        return GithubWebhookTest::successfulExecutionProvider();
    }

    public function testInvalidJsonPayload(): void
    {
        /** @var string $testTempDir */
        $testTempDir = self::$kernel->getContainer()->getParameter('test_tmp_dir');
        $githubEventPayloadPathName = $testTempDir.'/github_event/test.json';
        $this->fs->dumpFile($githubEventPayloadPathName, '{"action"@: "not_supported_action"}');

        $statusCode = $this->commandTester->execute([
            'event-type' => 'not_supported_event_type',
            'event-path-name' => $githubEventPayloadPathName,
        ]);

        $this->assertStringContainsString('Error on json', $this->commandTester->getDisplay());
        $this->assertSame(1, $statusCode);

        $this->fs->remove($githubEventPayloadPathName);
    }
}
