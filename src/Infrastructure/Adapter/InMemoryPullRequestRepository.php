<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPullRequestRepository implements PullRequestRepositoryInterface
{
    /** @var PullRequest[] */
    private $prs = [];

    public function find(PullRequestId $pullRequestId): ?PullRequest
    {
        $pr = $this->prs[$this->getIdByPrId(PullRequestId::create($pullRequestId->repositoryOwner, $pullRequestId->repositoryName, $pullRequestId->pullRequestNumber))] ?? null;
        if ($pr instanceof PullRequest) {
            $pr = clone $pr;
        }

        return $pr;
    }

    /**
     * @param PullRequest[] $prs
     */
    public function feed(array $prs): void
    {
        foreach ($prs as $pr) {
            $this->prs[$this->getIdByPrId($pr->getId())] = $pr;
        }
    }

    public function update(PullRequest $pullRequest): void
    {
        $this->prs[$this->getIdByPrId($pullRequest->getId())] = $pullRequest;
    }

    private function getIdByPrId(PullRequestId $prId): string
    {
        return $prId->repositoryOwner.'-'.$prId->repositoryName.'-'.$prId->pullRequestNumber;
    }
}
