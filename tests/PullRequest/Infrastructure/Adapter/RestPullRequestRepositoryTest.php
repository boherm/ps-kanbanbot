<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Infrastructure\Adapter;

use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Infrastructure\Adapter\RestPullRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RestPullRequestRepositoryTest extends KernelTestCase
{
    public function testFindUpdateMethods(): void
    {
        $this->markTestSkipped('You should implement this test.');
    }
}
