<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[When(env: 'test')]
class SpyMessageBus implements MessageBusInterface
{
    /** @var object[]  */
    private array $dispatchedMessages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatchedMessages[] = $message;

        return new Envelope($message, $stamps);
    }

    /**
     * @return object[]
     */
    public function getDispatchedMessages(): array
    {
        return $this->dispatchedMessages;
    }

}