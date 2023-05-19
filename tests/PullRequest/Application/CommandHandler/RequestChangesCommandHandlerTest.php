<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\Infrastructure\Adapter\InMemoryPRRepository;
use App\Infrastructure\Adapter\SpyEventDispatcher;
use App\PullRequest\Application\Command\RequestChangesCommand;
use App\PullRequest\Application\CommandHandler\RequestChangesCommandHandler;
use App\PullRequest\Domain\Aggregate\PR\PR;
use App\PullRequest\Domain\Aggregate\PR\PRId;
use App\PullRequest\Domain\Event\ChangesRequested;
use App\PullRequest\Domain\Exception\PRNotFoundException;
use PHPUnit\Framework\TestCase;

// todo: test not null
class RequestChangesCommandHandlerTest extends TestCase
{
    private RequestChangesCommandHandler $requestChangesCommandHandler;
    private InMemoryPRRepository $prRepository;
    private SpyEventDispatcher $spyEventDispatcher;

    protected function setUp(): void
    {
        $this->prRepository = new InMemoryPRRepository();
        $this->spyEventDispatcher = new SpyEventDispatcher();
        $this->requestChangesCommandHandler = new RequestChangesCommandHandler($this->prRepository, $this->spyEventDispatcher);
    }

    public function testHandle(): void
    {
        $repositoryOwner = 'repositoryOwner';
        $repositoryName = 'repositoryName';
        $pullRequestNumber = 'pullRequestNumber';

        $this->prRepository->feed([
            // Todo: replace create by foundry
            PR::create(
                id: PRId::create(
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
        /** @var PR $pr */
        $pr = $this->prRepository->find($repositoryOwner, $repositoryName, $pullRequestNumber);
        // todo : add enum instead
        $this->assertContains('Waiting for author', $pr->getLabels());
        $this->assertEquals([
            new ChangesRequested(
                repositoryOwner: $repositoryOwner,
                repositoryName: $repositoryName,
                pullRequestNumber: $pullRequestNumber
            ),
        ], $this->spyEventDispatcher->getDispatchedEvents());
    }

        public function testPRNotFound(): void
        {
            $this->expectException(PRNotFoundException::class);
            try {
                $this->requestChangesCommandHandler->__invoke(new RequestChangesCommand(
                    repositoryOwner: 'fake',
                    repositoryName: 'fake',
                    pullRequestNumber: 'fake'
                ));
            } catch (PRNotFoundException) {
                $this->assertEmpty($this->spyEventDispatcher->getDispatchedEvents());
                throw new PRNotFoundException();
            }
        }
}
