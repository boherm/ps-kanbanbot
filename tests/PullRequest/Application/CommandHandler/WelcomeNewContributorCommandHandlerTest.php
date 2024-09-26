<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\WelcomeNewContributorCommand;
use App\PullRequest\Application\CommandHandler\WelcomeNewContributorCommandHandler;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Infrastructure\Adapter\InMemoryPullRequestRepository;
use App\Shared\Infrastructure\Adapter\InMemoryCommitterRepository;
use PHPUnit\Framework\TestCase;

class WelcomeNewContributorCommandHandlerTest extends TestCase
{
    private WelcomeNewContributorCommandHandler $welcomeNewContributorCommandHandler;
    private InMemoryPullRequestRepository $prRepository;
    private InMemoryCommitterRepository $committerRepository;

    protected function setUp(): void
    {
        $this->prRepository = $this->getMockBuilder(InMemoryPullRequestRepository::class)
            ->onlyMethods(['addWelcomeComment'])
            ->getMock();

        $this->committerRepository = $this->getMockBuilder(InMemoryCommitterRepository::class)
            ->onlyMethods(['isNewContributor'])
            ->getMock();

        $this->welcomeNewContributorCommandHandler = new WelcomeNewContributorCommandHandler($this->committerRepository, $this->prRepository);
    }

    /**
     * @dataProvider provideTestHandle
     */
    public function testHandle(PullRequestId $pullRequestId, string $contributor, bool $newContributor, bool $expectedComment): void
    {
        $this->prRepository->feed([
            PullRequest::create(
                id: $pullRequestId,
                labels: [],
                approvals: [],
                targetBranch: 'main',
            ),
        ]);

        // @phpstan-ignore-next-line
        $this->prRepository
            ->expects($expectedComment ? $this->once() : $this->never())
            ->method('addWelcomeComment')
            ->with($pullRequestId, 'fakeContributor');

        // @phpstan-ignore-next-line
        $this->committerRepository
            ->method('isNewContributor')
            ->willReturn($newContributor);

        $this->welcomeNewContributorCommandHandler->__invoke(new WelcomeNewContributorCommand(
            repositoryOwner: $pullRequestId->repositoryOwner,
            repositoryName: $pullRequestId->repositoryName,
            pullRequestNumber: $pullRequestId->pullRequestNumber,
            contributor: $contributor,
        ));
    }

    /**
     * @return array<array<int, PullRequestId|bool|string>>
     */
    public static function provideTestHandle(): array
    {
        return [
            [
                new PullRequestId(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ),
                'fakeContributor',
                false,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ),
                'fakeContributor',
                true,
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                'fakeContributor',
                false,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                'fakeContributor',
                true,
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                'dependabot[bot]',
                true,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                'ps-jarvis',
                true,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                'github-actions[bot]',
                true,
                false,
            ],
        ];
    }
}
