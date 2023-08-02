<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory\CommandFactory\Strategy\Exclusion;

use App\Shared\Infrastructure\Factory\CommandFactory\ExclusionStrategyInterface;

class ExcludedRepositoryStrategy implements ExclusionStrategyInterface
{
    public function __construct(
        /** @var array<string> */
        private readonly array $repoExcluded,
    ) {
    }

    /**
     * @param array{
     *     pull_request: array{
     *          base: array{
     *             repo: array{
     *                 name: string,
     *             },
     *          },
     *     }
     * } $payload
     */
    public function isExcluded(string $eventType, array $payload): bool
    {
        return in_array($payload['pull_request']['base']['repo']['name'], $this->repoExcluded);
    }
}
