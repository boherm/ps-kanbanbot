<?php

declare(strict_types=1);

namespace App\PullRequestDashboard\Infrastructure\Adapter;

use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard\PullRequestCardId;
use App\PullRequestDashboard\Domain\Gateway\CommitterRepositoryInterface;

class InMemoryCommitterRepository implements CommitterRepositoryInterface
{
    public function findAll(PullRequestCardId $pullRequestCardId): array
    {
        return [
            'lartist',
            'nicosomb',
        ];
    }
}
