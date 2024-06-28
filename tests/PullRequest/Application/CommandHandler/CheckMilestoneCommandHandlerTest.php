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
           ->onlyMethods([
               'addMissingMilestoneComment',
               'isMilestoneNeeded',
           ])
           ->getMock();
        $this->checkMilestoneCommandHandler = new CheckMilestoneCommandHandler($this->prRepository);
    }

    /**
     * @dataProvider provideTestHandle
     */
    public function testHandle(PullRequestId $pullRequestId, ?int $milestoneNumber, bool $milestoneNeeded, bool $expectedComment): void
    {
        $this->prRepository->feed([
            PullRequest::create(
                id: $pullRequestId,
                labels: [],
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
        // @phpstan-ignore-next-line
        $this->prRepository
            ->expects($this->once())
            ->method('isMilestoneNeeded')
            ->with($pullRequestId)
            ->willReturn($milestoneNeeded);

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
                null,
                true,
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ),
                null,
                false,
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ),
                123,
                true,
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
