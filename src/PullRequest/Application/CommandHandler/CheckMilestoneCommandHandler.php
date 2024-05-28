<?php

declare(strict_types=1);

namespace App\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\CheckMilestoneCommand;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Exception\PullRequestNotFoundException;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;

class CheckMilestoneCommandHandler
{
    public function __construct(
        private readonly PullRequestRepositoryInterface $prRepository
    ) {
    }

    public function __invoke(CheckMilestoneCommand $command): void
    {
        // This command is only for PrestaShop/PrestaShop repository
        if ('PrestaShop' !== $command->repositoryOwner || 'PrestaShop' !== $command->repositoryName) {
            return;
        }

        // Retrieve the PullRequest object
        $prId = new PullRequestId($command->repositoryOwner, $command->repositoryName, $command->pullRequestNumber);
        $pullRequest = $this->prRepository->find($prId);
        if (null === $pullRequest) {
            throw new PullRequestNotFoundException();
        }

        // We check if the milestone is set
        if (null === $pullRequest->getMilestoneNumber() && $pullRequest->isQAValidated()) {
            $this->prRepository->addMissingMilestoneComment($prId);
        }
    }
}
