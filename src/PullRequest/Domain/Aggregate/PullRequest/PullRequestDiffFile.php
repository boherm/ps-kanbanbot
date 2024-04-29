<?php

declare(strict_types=1);

namespace App\PullRequest\Domain\Aggregate\PullRequest;

class PullRequestDiffFile
{
    public function __construct(
        private readonly string $filename,
        /** @var PullRequestDiffHunk[] $hunks */
        private readonly array $hunks = [],
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return PullRequestDiffHunk[]
     */
    public function getHunks(): array
    {
        return $this->hunks;
    }
}
