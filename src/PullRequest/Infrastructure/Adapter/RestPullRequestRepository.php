<?php

declare(strict_types=1);

namespace App\PullRequest\Infrastructure\Adapter;

use App\PullRequest\Domain\Aggregate\PullRequest\Approval;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RestPullRequestRepository implements PullRequestRepositoryInterface
{
    public function __construct(private readonly HttpClientInterface $githubClient)
    {
    }

    public function find(PullRequestId $pullRequestId): ?PullRequest
    {
        $response = $this->githubClient->request('GET', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/pulls/'.$pullRequestId->pullRequestNumber);

        return PullRequest::create(
            $pullRequestId,
            array_map(
                static function (array $label): string {
                    return $label['name'];
                },
                $response->toArray()['labels']
            ),
            $this->getApprovals($pullRequestId)
        );
    }

    public function update(PullRequest $pullRequest): void
    {
        $this->githubClient->request(
            'PATCH',
            '/repos/'.$pullRequest->getId()->repositoryOwner.'/'.$pullRequest->getId()->repositoryName.'/issues/'.$pullRequest->getId()->pullRequestNumber,
            [
                'json' => [
                    'labels' => $pullRequest->getLabels(),
                ],
            ]
        );
    }

    /**
     * @return Approval[]
     */
    public function getApprovals(PullRequestId $pullRequestId): array
    {
        $reviews = $this->githubClient->request('GET', '/repos/'.$pullRequestId->repositoryOwner.'/'.$pullRequestId->repositoryName.'/pulls/'.$pullRequestId->pullRequestNumber.'/reviews')->toArray();

        $approvals = [];
        foreach ($reviews as $review) {
            if ('APPROVED' === $review['state']) {
                $approvals[$review['user']['login']] = new Approval($review['user']['login']);
            } elseif ('CHANGES_REQUESTED' === $review['state'] || 'DISMISSED' === $review['state']) {
                unset($approvals[$review['user']['login']]);
            }
        }

        return $approvals;
    }
}
