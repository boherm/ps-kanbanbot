<?php

declare(strict_types=1);

namespace App\PullRequest\Application\CommandHandler;

use App\PullRequest\Application\Command\CheckTableDescriptionCommand;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestDescription;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Exception\PullRequestNotFoundException;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckTableDescriptionCommandHandler
{
    public function __construct(
        private readonly PullRequestRepositoryInterface $prRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CheckTableDescriptionCommand $command): void
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

        // Create PulLRequestDescription object with the description
        $prDescription = new PullRequestDescription($pullRequest->getBodyDescription());

        // We check the description with the validator
        $errors = $this->validator->validate($prDescription);

        // If we have some errors, we need to add (or edit) the comment about this errors
        if (count($errors) > 0 || ($prDescription->isLinkedIssuesNeeded() && !$prDescription->hasLinkedIssues())) {
            $this->prRepository->addTableDescriptionErrorsComment($prId, $errors, $prDescription->isLinkedIssuesNeeded());
        } else {
            $this->prRepository->removeTableDescriptionErrorsComment($prId);
        }

        // Then, we had the labels to the PR by PullRequestDescription object
        $pullRequest->addLabelsByDescription($prDescription);
        $this->prRepository->update($pullRequest);
    }
}
