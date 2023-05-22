<?php

declare(strict_types=1);

namespace App\PullRequest\Domain\Aggregate\PullRequest;

class PullRequest
{
    /**
     * @param string[] $labels
     */
    private function __construct(
        private PullRequestId $id,
        private array $labels,
    ) {
    }

    /**
     * @param string[] $labels
     */
    public static function create(PullRequestId $id, array $labels): self
    {
        return new self($id, $labels);
    }

    public function getId(): PullRequestId
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function requestChanges(): void
    {
        // todo: test if already exists
        $this->labels[] = 'Waiting for author';
    }
}
