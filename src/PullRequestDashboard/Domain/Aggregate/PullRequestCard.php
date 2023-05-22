<?php

declare(strict_types=1);

namespace App\PullRequestDashboard\Domain\Aggregate;

class PullRequestCard
{


    private function __construct(
        private PullRequestCardId $id,
        private string            $columnName
    ) {
    }

    public static function create(PullRequestCardId $id, string $columnName): self
    {
        return new self($id, $columnName);
    }

    public function getId(): PullRequestCardId
    {
        return $this->id;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function moveColumnTo(string $columnName): void
    {
        $this->columnName = $columnName;
    }
}
