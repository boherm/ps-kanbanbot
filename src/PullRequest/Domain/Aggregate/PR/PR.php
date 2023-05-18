<?php

declare(strict_types=1);

namespace App\PullRequest\Domain\Aggregate\PR;

class PR
{
    private function __construct(
        private PRId $id,
        private array $labels,
    )
    {
    }

    public static function create(PRId $id, array $labels): self
    {
        return new self($id, $labels);
    }

    public function getId(): PRId
    {
        return $this->id;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function requestChanges()
    {
        //todo: test if already exists
        $this->labels[] = 'Waiting for author';
    }
}