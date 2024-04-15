<?php

declare(strict_types=1);

namespace App\PullRequest\Domain\Aggregate\PullRequest;

class PullRequest
{
    /**
     * @param string[]   $labels
     * @param Approval[] $approvals
     */
    private function __construct(
        private PullRequestId $id,
        private array $labels,
        private array $approvals,
        private string $targetBranch,
    ) {
    }

    /**
     * @param string[]   $labels
     * @param Approval[] $approvals
     */
    public static function create(PullRequestId $id, array $labels, array $approvals, string $targetBranch): self
    {
        return new self($id, $labels, $approvals, $targetBranch);
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
        if (!in_array('Waiting for author', $this->labels, true)) {
            $this->labels[] = 'Waiting for author';
        }
    }

    public function waitingForWording(): void
    {
        if (!in_array('Waiting for wording', $this->labels, true)) {
            $this->labels[] = 'Waiting for wording';
        }
    }

    /**
     * @param string[] $committers
     */
    public function addLabelByApprovalCount(array $committers): void
    {
        $validApprovals = array_filter(
            $this->approvals,
            static fn (Approval $approval) => in_array($approval->author, $committers, true)
        );
        if (
            'PrestaShop' === $this->id->repositoryOwner
            && 'PrestaShop' === $this->id->repositoryName
            && 2 === count($validApprovals)
        ) {
            $this->labels[] = 'Waiting for QA';
        }
        if (
            'PrestaShop' === $this->id->repositoryOwner
            && 'PrestaShop' !== $this->id->repositoryName
            && 1 === count($validApprovals)
        ) {
            $this->labels[] = 'Waiting for QA';
        }
    }

    public function getTargetBranch(): string
    {
        return $this->targetBranch;
    }
}
