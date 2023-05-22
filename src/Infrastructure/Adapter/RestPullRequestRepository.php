<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

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
            )
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
}
