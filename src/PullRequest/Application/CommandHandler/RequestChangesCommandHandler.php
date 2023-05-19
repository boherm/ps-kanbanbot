<?php

declare(strict_types=1);

namespace App\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\RequestChangesCommand;
use App\PullRequest\Domain\Event\ChangesRequested;
use App\PullRequest\Domain\Exception\PRNotFoundException;
use App\PullRequest\Domain\Gateway\PRRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RequestChangesCommandHandler
{

    public function __construct(
        private readonly PRRepositoryInterface $prRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function __invoke(RequestChangesCommand $command): void
    {
        $pr = $this->prRepository->find($command->repositoryOwner, $command->repositoryName, $command->pullRequestNumber);
        if ($pr === null) {
            throw new PRNotFoundException();
        }
        $pr->requestChanges();
        $this->prRepository->update($pr);
        $this->eventDispatcher->dispatch(new ChangesRequested(
            repositoryOwner: $command->repositoryOwner,
            repositoryName: $command->repositoryName,
            pullRequestNumber: $command->pullRequestNumber
        ));
    }

}