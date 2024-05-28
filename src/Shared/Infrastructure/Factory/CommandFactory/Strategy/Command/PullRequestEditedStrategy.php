<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory\CommandFactory\Strategy\Command;

use App\PullRequest\Application\Command\CheckTableDescriptionCommand;
use App\Shared\Infrastructure\Factory\CommandFactory\CommandStrategyInterface;

class PullRequestEditedStrategy implements CommandStrategyInterface
{
    /**
     * @param array{
     *     action: string,
     *     pull_request: array{
     *          base: array{
     *             repo: array{
     *                 name: string,
     *             },
     *          },
     *          state: string,
     *          draft: bool,
     *     }
     * } $payload
     */
    public function supports(string $eventType, array $payload): bool
    {
        return 'pull_request' === $eventType
            and 'edited' === $payload['action']
            and 'open' === $payload['pull_request']['state'];
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
     * @return array<CheckTableDescriptionCommand>
     */
    public function createCommandsFromPayload(array $payload): array
    {
        $repoOwner = $payload['pull_request']['base']['repo']['owner']['login'];
        $repoName = $payload['pull_request']['base']['repo']['name'];
        $prNumber = (string) $payload['pull_request']['number'];

        return [
            new CheckTableDescriptionCommand(
                $repoOwner,
                $repoName,
                $prNumber,
            ),
        ];
    }
}
