<?php

namespace App\Shared\Infrastructure\Factory\CommandFactory;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shared.exclusion_strategy')]
interface ExclusionStrategyInterface
{
    /**
     * @param array<mixed> $payload
     */
    public function isExcluded(string $eventType, array $payload): bool;
}
