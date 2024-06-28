<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory\CommandFactory\Strategy\Command;

use App\PullRequest\Application\Command\CheckMilestoneCommand;
use App\PullRequestDashboard\Application\Command\MovePullRequestCardToColumnByLabelCommand;
use App\Shared\Infrastructure\Factory\CommandFactory\CommandStrategyInterface;

class PullRequestMergedStrategy implements CommandStrategyInterface
{
    public function __construct(
        private readonly string $pullRequestDashboardNumber,
        private readonly string $mergedColumnName,
    ) {
    }

    /**
     * @param array{
     *     action: string,
     *     pull_request: array{
     *          merged: bool,
     *     }
     * } $payload
     */
    public function supports(string $eventType, array $payload): bool
    {
        return 'pull_request' === $eventType and 'closed' === $payload['action'] and true === $payload['pull_request']['merged'];
    }

    /**
     * @param array{
     *     pull_request: array{
     *         base: array{
     *             repo: array{
     *                 name: string,
     *                 owner: array{
     *                     login: string
     *                 }
     *             }
     *         },
     *         number: int,
     *     },
     * } $payload
     *
     * @return array<MovePullRequestCardToColumnByLabelCommand|CheckMilestoneCommand>
     */
    public function createCommandsFromPayload(array $payload): array
    {
        $repoOwner = $payload['pull_request']['base']['repo']['owner']['login'];
        $repoName = $payload['pull_request']['base']['repo']['name'];
        $prNumber = (string) $payload['pull_request']['number'];

        return [
            new MovePullRequestCardToColumnByLabelCommand(
                $this->pullRequestDashboardNumber,
                $repoOwner,
                $repoName,
                $prNumber,
                $this->mergedColumnName,
            ),
            new CheckMilestoneCommand(
                $repoOwner,
                $repoName,
                $prNumber,
            ),
        ];
    }
}
