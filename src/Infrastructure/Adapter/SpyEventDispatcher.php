<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class SpyEventDispatcher implements EventDispatcherInterface
{
    /** @var object[]  */
    private array $dispatchedEvents = [];

    public function dispatch(object $event): object
    {
        $this->dispatchedEvents[] = $event;
        return $event;
    }

    /**
     * @return object[]
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }
}