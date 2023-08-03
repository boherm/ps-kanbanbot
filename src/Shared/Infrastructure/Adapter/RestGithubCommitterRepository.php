<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Domain\Gateway\CommitterRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RestGithubCommitterRepository implements CommitterRepositoryInterface
{
    public function __construct(private readonly HttpClientInterface $githubClient)
    {
    }

    public function findAll(string $organisation): array
    {
        return array_map(
            fn (array $committer) => $committer['login'],
            $this->githubClient->request('GET', '/orgs/'.$organisation.'/teams/committers/members')->toArray()
        );
    }
}
