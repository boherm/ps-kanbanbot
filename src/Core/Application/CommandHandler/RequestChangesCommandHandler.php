<?php

declare(strict_types=1);

namespace App\Core\Application\CommandHandler;

use App\Core\Application\Command\RequestChangesCommand;
use App\Core\Domain\Gateway\PRRepositoryInterface;

class RequestChangesCommandHandler
{

    public function __construct(
        private readonly PRRepositoryInterface $prRepository
    )
    {
    }

    public function __invoke(RequestChangesCommand $command): void
    {
        $pr = $this->prRepository->find($command->repositoryOwner, $command->repositoryName, $command->pullRequestNumber);
        $pr->requestChanges();
        $this->prRepository->update($pr);
    }

}