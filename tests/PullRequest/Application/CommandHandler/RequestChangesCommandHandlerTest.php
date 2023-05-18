<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Application\CommandHandler;

use App\PullRequest\Domain\Event\ChangesRequested;
use App\Infrastructure\Adapter\InMemoryPRRepository;
use App\PullRequest\Application\Command\RequestChangesCommand;
use App\PullRequest\Application\CommandHandler\RequestChangesCommandHandler;
use App\PullRequest\Domain\Aggregate\PR\PR;
use App\PullRequest\Domain\Aggregate\PR\PRId;
use App\Infrastructure\Adapter\SpyEventDispatcher;
use PHPUnit\Framework\TestCase;

class RequestChangesCommandHandlerTest extends TestCase
{

        public function testHandle(): void
        {
            $prRepository = new InMemoryPRRepository();
            $spyEventDispatcher = new SpyEventDispatcher();
            $repositoryOwner = 'repositoryOwner';
            $repositoryName = 'repositoryName';
            $pullRequestNumber = 'pullRequestNumber';

            $prRepository->feed([
                //Todo: replace create by foundry
                PR::create(
                    id: PRId::create(
                        repositoryOwner: $repositoryOwner,
                        repositoryName: $repositoryName,
                        pullRequestNumber: $pullRequestNumber
                    ),
                    labels: []
                )
            ]);

            $addLabelCommandHandler = new RequestChangesCommandHandler($prRepository, $spyEventDispatcher);
            $addLabelCommandHandler->__invoke(new RequestChangesCommand(
                repositoryOwner: $repositoryOwner,
                repositoryName: $repositoryName,
                pullRequestNumber: $pullRequestNumber
            ));
            $pr = $prRepository->find($repositoryOwner, $repositoryName, $pullRequestNumber);
            //todo : add enum instead
            $this->assertContains('Waiting for author', $pr->getLabels());
            $this->assertEquals([
                new ChangesRequested(
                    repositoryOwner: $repositoryOwner,
                    repositoryName: $repositoryName,
                    pullRequestNumber: $pullRequestNumber
                )
            ], $spyEventDispatcher->getDispatchedEvents());
        }
}