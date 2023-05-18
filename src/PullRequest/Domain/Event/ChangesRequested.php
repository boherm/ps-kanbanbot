<?php

declare(strict_types=1);

namespace App\PullRequest\Domain\Event;

class ChangesRequested
{
    public function __construct(
        public readonly string $repositoryOwner,
        public readonly string $repositoryName,
        public readonly string $pullRequestNumber
    )
    {
    }
}