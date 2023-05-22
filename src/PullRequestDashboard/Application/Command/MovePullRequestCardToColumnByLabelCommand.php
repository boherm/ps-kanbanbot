<?php

declare(strict_types=1);

namespace App\PullRequestDashboard\Application\Command;

class MovePullRequestCardToColumnByLabelCommand
{

    public function __construct(
        public readonly string $projectNumber,
        public readonly string $repositoryOwner,
        public readonly string $repositoryName,
        public readonly string $pullRequestNumber,
        public readonly string $columnName,
    )
    {
    }
}