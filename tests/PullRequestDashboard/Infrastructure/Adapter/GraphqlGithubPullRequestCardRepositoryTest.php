<?php

declare(strict_types=1);

namespace App\Tests\PullRequestDashboard\Infrastructure\Adapter;

use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard\Approval;
use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard\PullRequest;
use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard\PullRequestCard;
use App\PullRequestDashboard\Domain\Aggregate\PullRequestCard\PullRequestCardId;
use App\PullRequestDashboard\Infrastructure\Adapter\GraphqlGithubPullRequestCardRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GraphqlGithubPullRequestCardRepositoryTest extends KernelTestCase
{
    public function testUpdateMethod(): void
    {
        $this->markTestSkipped('You should implement this test.');
    }
}
