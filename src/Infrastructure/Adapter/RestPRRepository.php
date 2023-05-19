<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\PullRequest\Domain\Aggregate\PR\PR;
use App\PullRequest\Domain\Aggregate\PR\PRId;
use App\PullRequest\Domain\Gateway\PRRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RestPRRepository implements PRRepositoryInterface
{
    public function __construct(private readonly HttpClientInterface $githubClient)
    {
    }

    public function find(string $repositoryOwner, string $repositoryName, string $pullRequestNumber): ?PR
    {
        $response = $this->githubClient->request('GET', '/repos/'.$repositoryOwner.'/'.$repositoryName.'/pulls/'.$pullRequestNumber);

        return PR::create(
            PRId::create($repositoryOwner, $repositoryName, $pullRequestNumber),
            array_map(
                static function (array $label): string {
                    return $label['name'];
                },
                $response->toArray()['labels']
            )
        );
    }

    public function update(PR $pr): void
    {
        // todo: refacto url building
        $this->githubClient->request(
            'PATCH',
            '/repos/'.$pr->getId()->repositoryOwner.'/'.$pr->getId()->repositoryName.'/issues/'.$pr->getId()->pullRequestNumber,
            [
                'json' => [
                    'labels' => $pr->getLabels(),
                ],
            ]
        );
    }
}
