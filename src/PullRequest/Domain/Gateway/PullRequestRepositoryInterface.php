<?php

namespace App\PullRequest\Domain\Gateway;

use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;

interface PullRequestRepositoryInterface
{
    public function find(PullRequestId $pullRequestId): ?PullRequest;

    public function update(PullRequest $pullRequest): void;
}
