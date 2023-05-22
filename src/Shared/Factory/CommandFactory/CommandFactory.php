<?php

declare(strict_types=1);

namespace App\Shared\Factory\CommandFactory;

class CommandFactory
{
    /**
     * @param iterable<CommandStrategyInterface> $commandStrategies
     */
    public function __construct(private readonly iterable $commandStrategies)
    {
    }

    public function fromEventTypeAndPayload(string $eventType, string $payload): ?object
    {
        //Todo: to test error on json
        $payload = json_decode($payload, true);
        foreach ($this->commandStrategies as $commandStrategy) {
            if ($commandStrategy->supports($eventType, $payload)) {
                return $commandStrategy->createCommandFromPayload($payload);
            }
        }
        //Todo: to test
        return null;
    }
}