<?php

namespace App\Core\Domain\Gateway;

use App\Core\Domain\Aggregate\PR\PR;

interface PRRepositoryInterface
{
    public function find(string $repositoryOwner, string $repositoryName, string $pullRequestNumber): ?PR;

    public function update(PR $pr): void;
}