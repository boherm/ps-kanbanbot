<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\CheckSecurityBranchCommand;
use App\PullRequest\Application\CommandHandler\CheckSecurityBranchCommandHandler;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Exception\PullRequestNotFoundException;
use App\PullRequest\Infrastructure\Adapter\InMemoryPullRequestRepository;
use PHPUnit\Framework\TestCase;

class CheckSecurityBranchCommandHandlerTest extends TestCase
{
    private CheckSecurityBranchCommandHandler $checkSecurityBranchCommandHandler;
    private InMemoryPullRequestRepository $prRepository;

    protected function setUp(): void
    {
        $this->prRepository = $this->getMockBuilder(InMemoryPullRequestRepository::class)
           ->onlyMethods([
               'addSecurityBranchComment',
           ])
           ->getMock();
        $this->checkSecurityBranchCommandHandler = new CheckSecurityBranchCommandHandler($this->prRepository);
    }

    /**
     * @dataProvider provideTestHandle
     */
    public function testHandle(PullRequestId $pullRequestId, string $targetBranch, bool $expectedComment): void
    {
        $this->prRepository->feed([
            PullRequest::create(
                id: $pullRequestId,
                labels: [],
                approvals: [],
                targetBranch: $targetBranch
            ),
        ]);

        // @phpstan-ignore-next-line
        $this->prRepository
            ->expects($expectedComment ? $this->once() : $this->never())
            ->method('addSecurityBranchComment')
            ->with($pullRequestId, $targetBranch);

        $this->checkSecurityBranchCommandHandler->__invoke(new CheckSecurityBranchCommand(
            repositoryOwner: $pullRequestId->repositoryOwner,
            repositoryName: $pullRequestId->repositoryName,
            branchName: $targetBranch,
            pullRequestNumber: $pullRequestId->pullRequestNumber,
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
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                'develop',
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '9.1.x',
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '9.0.x',
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '8.2.x',
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '8.1.x',
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '8.0.x',
                true,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'PrestaShop',
                    repositoryName: 'PrestaShop',
                    pullRequestNumber: 'fake'
                ),
                '1.7.8.x',
                false,
            ],
            [
                new PullRequestId(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ),
                '8.1.x',
                false,
            ],
        ];
    }

    public function testPullRequestNotFound(): void
    {
        $this->expectException(PullRequestNotFoundException::class);
        $this->checkSecurityBranchCommandHandler->__invoke(new CheckSecurityBranchCommand(
            repositoryOwner: 'PrestaShop',
            repositoryName: 'PrestaShop',
            branchName: '8.1.x',
            pullRequestNumber: 'fake'
        ));
    }
}
