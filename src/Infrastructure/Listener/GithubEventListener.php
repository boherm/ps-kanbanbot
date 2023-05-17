<?php

declare(strict_types=1);

namespace App\Infrastructure\Listener;

use App\Core\Application\Command\RequestChangesCommand;
use App\Infrastructure\Event\GithubEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
class GithubEventListener
{

    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function __invoke(GithubEvent $event): void
    {
        //todo: test and use enum
        $payloadAsArray = json_decode($event->payload, true);

        if ($event->eventType !== 'pull_request_review' || $payloadAsArray['action'] !== 'submitted') {
            return;
        }

        $this->commandBus->dispatch(new RequestChangesCommand(
            $payloadAsArray['pull_request']['base']['repo']['owner']['login'],
            $payloadAsArray['pull_request']['base']['repo']['name'],
            (string) $payloadAsArray['pull_request']['number']
        ));
    }
}