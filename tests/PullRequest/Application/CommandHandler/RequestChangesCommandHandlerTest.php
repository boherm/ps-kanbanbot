<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\Infrastructure\Adapter\InMemoryPullRequestRepository;
use App\PullRequest\Application\Command\RequestChangesCommand;
use App\PullRequest\Application\CommandHandler\RequestChangesCommandHandler;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Exception\PullRequestNotFoundException;
use PHPUnit\Framework\TestCase;

class RequestChangesCommandHandlerTest extends TestCase
{
    private RequestChangesCommandHandler $requestChangesCommandHandler;
    private InMemoryPullRequestRepository $prRepository;

    protected function setUp(): void
    {
        $this->prRepository = new InMemoryPullRequestRepository();
        $this->requestChangesCommandHandler = new RequestChangesCommandHandler($this->prRepository);
    }

    public function testHandle(): void
    {
        $repositoryOwner = 'repositoryOwner';
        $repositoryName = 'repositoryName';
        $pullRequestNumber = 'pullRequestNumber';

        $this->prRepository->feed([
            PullRequest::create(
                id: PullRequestId::create(
                    repositoryOwner: $repositoryOwner,
                    repositoryName: $repositoryName,
                    pullRequestNumber: $pullRequestNumber
                ),
                labels: []
            ),
        ]);

        $this->requestChangesCommandHandler->__invoke(new RequestChangesCommand(
            repositoryOwner: $repositoryOwner,
            repositoryName: $repositoryName,
            pullRequestNumber: $pullRequestNumber
        ));
        /** @var PullRequest $pr */
        $pr = $this->prRepository->find(
            PullRequestId::create(repositoryOwner: $repositoryOwner, repositoryName: $repositoryName, pullRequestNumber: $pullRequestNumber)
        );
        // todo : add enum instead
        $this->assertContains('Waiting for author', $pr->getLabels());
    }

        public function testPullRequestNotFound(): void
        {
            $this->expectException(PullRequestNotFoundException::class);
            $this->requestChangesCommandHandler->__invoke(new RequestChangesCommand(
                repositoryOwner: 'fake',
                repositoryName: 'fake',
                pullRequestNumber: 'fake'
            ));
        }
}
