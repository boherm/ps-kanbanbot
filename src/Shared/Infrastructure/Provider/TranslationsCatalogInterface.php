<?php

namespace App\Shared\Infrastructure\Provider;

use App\PullRequest\Domain\Aggregate\PullRequest\PullRequest;

interface TranslationsCatalogInterface
{
    public function getCatalogVersionByPullRequest(PullRequest $pullRequest): int;

    /**
     * @return array<int, string>
     */
    public function getTranslationsCatalog(string $locale, string $domain, int $PSversion = 9): array;
}
