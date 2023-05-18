<?php

namespace App\PullRequest\Domain\Gateway;

use App\PullRequest\Domain\Aggregate\PR\PR;

interface PRRepositoryInterface
{
    public function find(string $repositoryOwner, string $repositoryName, string $pullRequestNumber): ?PR;

    public function update(PR $pr): void;
}