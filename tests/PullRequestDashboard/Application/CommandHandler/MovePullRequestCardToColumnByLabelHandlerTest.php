<?php

declare(strict_types=1);

namespace App\Tests\PullRequestDashboard\Application\CommandHandler;

use App\PullRequestDashboard\Application\Command\MovePullRequestCardToColumnByLabelCommand;
use App\PullRequestDashboard\Application\CommandHandler\MovePullRequestCardToColumnByLabelCommandHandler;
use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard;
use App\PullRequestDashboard\Domain\Aggregate\PullRequestCardId;
use App\PullRequestDashboard\Domain\Exception\PullRequestCardNotFoundException;
use App\PullRequestDashboard\Infrastructure\Adapter\InMemoryPullRequestPullRequestCardRepositoryInterface;
use PHPUnit\Framework\TestCase;

class MovePullRequestCardToColumnByLabelHandlerTest extends TestCase
{
    private MovePullRequestCardToColumnByLabelCommandHandler $movePullRequestCardToColumnByLabelHandler;
    private InMemoryPullRequestPullRequestCardRepositoryInterface $pullRequestCardRepository;

    protected function setUp(): void
    {
        $this->pullRequestCardRepository = new InMemoryPullRequestPullRequestCardRepositoryInterface();
        $this->movePullRequestCardToColumnByLabelHandler = new MovePullRequestCardToColumnByLabelCommandHandler($this->pullRequestCardRepository);
    }
    public function testHandle(): void
    {
        $projectNumber = '17';
        $repositoryOwner = 'repositoryOwner';
        $repositoryName = 'repositoryName';
        $pullRequestNumber = 'pullRequestNumber';

        $this->pullRequestCardRepository->feed([
            PullRequestCard::create(
                id: new PullRequestCardId(
                    projectNumber: $projectNumber,
                    repositoryOwner: $repositoryOwner,
                    repositoryName: $repositoryName,
                    pullRequestNumber: $pullRequestNumber,
                ),
                columnName: 'Waiting for author'
            ),
        ]);

        $this->movePullRequestCardToColumnByLabelHandler->__invoke(new MovePullRequestCardToColumnByLabelCommand(
            projectNumber: $projectNumber,
            repositoryOwner: $repositoryOwner,
            repositoryName: $repositoryName,
            pullRequestNumber: $pullRequestNumber,
            columnName: 'Waiting for review'
        ));
        /** @var PullRequestCard $pullRequestCard */
        $pullRequestCard = $this->pullRequestCardRepository->find(
            new PullRequestCardId(
                projectNumber: $projectNumber,
                repositoryOwner: $repositoryOwner,
                repositoryName: $repositoryName,
                pullRequestNumber: $pullRequestNumber
            )
        );
        // todo : add enum instead
        $this->assertSame('Waiting for review', $pullRequestCard->getColumnName());
    }

    public function testPullRequestCardNotFound(): void
    {
        $this->expectException(PullRequestCardNotFoundException::class);
        $this->movePullRequestCardToColumnByLabelHandler->__invoke(new MovePullRequestCardToColumnByLabelCommand(
            projectNumber: 'fake',
            repositoryOwner: 'fake',
            repositoryName: 'fake',
            pullRequestNumber: 'fake',
            columnName: 'fake'
        ));
    }
}