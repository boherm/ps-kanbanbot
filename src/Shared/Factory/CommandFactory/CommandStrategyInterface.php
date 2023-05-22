<?php

namespace App\Shared\Factory\CommandFactory;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shared.command_strategy')]
interface CommandStrategyInterface
{
    public function supports(string $eventType, array $payload): bool;

    public function createCommandFromPayload(array $payload): object;
}