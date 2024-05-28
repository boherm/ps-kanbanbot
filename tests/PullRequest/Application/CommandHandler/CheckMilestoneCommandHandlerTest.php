<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\CheckMilestoneCommand;
use App\PullRequest\Application\CommandHandler\CheckMilestoneCommandHandler;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Exception\PullRequestNotFoundException;
use App\PullRequest\Infrastructure\Adapter\InMemoryPullRequestRepository;
use PHPUnit\Framework\TestCase;

class CheckMilestoneCommandHandlerTest extends TestCase
{
    private CheckMilestoneCommandHandler $checkMilestoneCommandHandler;
    private InMemoryPullRequestRepository $prRepository;

    protected function setUp(): void
    {
        $this->prRepository = $this->getMockBuilder(InMemoryPullRequestRepository::class)
            ->onlyMethods(['addMissingMilestoneComment'])
            ->getMock();
        $this->checkMilestoneCommandHandler = new CheckMilestoneCommandHandler($this->prRepository);
    }

    /**
     * @param string[] $originalLabels
     *
     * @dataProvider provideTestHandle
     */
    public function testHandle(PullRequestId $pullRequestId, array $originalLabels, ?int $milestoneNumber, bool $expectedComment): void
    {
        $this->prRepository->feed([
            PullRequest::create(
                id: $pullRequestId,
                labels: $originalLabels,
                approvals: [],
                targetBranch: 'main',
                milestoneNumber: $milestoneNumber
            ),
        ]);

        // @phpstan-ignore-next-line
        $this->prRepository
            ->expects($expectedComment ? $this->once() : $this->never())
            ->method('addMissingMilestoneComment')
            ->with($pullRequestId);

        $this->checkMilestoneCommandHandler->__invoke(new CheckMilestoneCommand(
            repositoryOwner: $pullRequestId->repositoryOwner,
            repositoryName: $pullRequestId->repositoryName,
            pullRequestNumber: $pullRequestId->pullRequestNumber,
        ));
    }

    /**
     * @return array<array<int, array<int, string>|PullRequestId|bool|int|null>>
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
                ['QA ✔️'],
                null,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                ['QA ✔️'],
                123,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                ['QA ✔️'],
                null,
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'pullRequestNumber'
                ),
                ['Waiting for QA'],
                null,
                false,
            ],
        ];
    }

    public function testPullRequestNotFound(): void
    {
        $this->expectException(PullRequestNotFoundException::class);
        $this->checkMilestoneCommandHandler->__invoke(new CheckMilestoneCommand(
            repositoryOwner: 'PrestaShop',
            repositoryName: 'PrestaShop',
            pullRequestNumber: 'fake'
        ));
    }
}
