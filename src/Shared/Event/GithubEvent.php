<?php

declare(strict_types=1);

namespace App\Shared\Event;

class GithubEvent
{
    public function __construct(
        public readonly string $eventType,
        public readonly string $payload,
    ) {
    }
}
